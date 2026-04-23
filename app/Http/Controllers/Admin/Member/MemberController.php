<?php

namespace App\Http\Controllers\Admin\Member;

use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Member;
use App\Services\Member\MemberDocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function __construct(
        private readonly MemberDocumentService $documentService
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status', 'all');

        $members = Member::query()
            ->withCount(['appointments', 'contactMessages'])
            ->search($search)
            ->when($status === 'active', fn ($query) => $query->active())
            ->when($status === 'suspended', fn ($query) => $query->suspended())
            ->when($status === 'document', fn ($query) => $query->withDocument())
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.pages.members.index', [
            'pageTitle' => 'Üyelik Yönetimi',
            'members' => $members,
            'search' => $search,
            'status' => $status,
            'stats' => [
                'all' => Member::query()->count(),
                'active' => Member::query()->active()->count(),
                'suspended' => Member::query()->suspended()->count(),
                'documents' => Member::query()->withDocument()->count(),
            ],
        ]);
    }

    public function show(Member $member): View
    {
        $member->loadCount([
            'appointments',
            'contactMessages',
            'appointments as active_appointments_count' => fn ($query) => $query
                ->where('status', Appointment::STATUS_BOOKED)
                ->where('end_at', '>=', now()),
        ]);

        $member->load([
            'appointments' => fn ($query) => $query
                ->with('provider:id,name')
                ->latest('start_at')
                ->limit(8),
            'contactMessages' => fn ($query) => $query
                ->latest('created_at')
                ->limit(6),
        ]);

        return view('admin.pages.members.show', [
            'pageTitle' => 'Üye Profili',
            'member' => $member,
        ]);
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('members', 'email')->ignore($member->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'suspension_reason' => ['nullable', 'string', 'max:255'],
            'filepath' => ['nullable', 'file', 'max:12288', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
            'clear_document' => ['nullable', 'boolean'],
        ]);

        $isActive = (bool) ($validated['is_active'] ?? false);
        $suspensionReason = filled($validated['suspension_reason'] ?? null)
            ? trim((string) $validated['suspension_reason'])
            : null;

        DB::transaction(function () use ($member, $validated, $request, $isActive, $suspensionReason) {
            $payload = [
                'name' => trim((string) $validated['name']),
                'surname' => trim((string) $validated['surname']),
                'email' => Str::lower(trim((string) $validated['email'])),
                'phone' => filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null,
                'is_active' => $isActive,
                'suspended_at' => $isActive ? null : ($member->suspended_at ?: now()),
                'suspension_reason' => $isActive ? null : ($suspensionReason ?: 'Yönetici işlemi ile askıya alındı.'),
            ];

            if (filled($validated['password'] ?? null)) {
                $payload['password'] = (string) $validated['password'];
            }

            $member->update($payload);

            $this->documentService->sync(
                $member,
                $request->file('filepath'),
                (bool) ($validated['clear_document'] ?? false)
            );
        });

        return redirect()
            ->route('admin.members.show', $member)
            ->with('success', 'Üye kaydı güncellendi.');
    }

    public function toggleStatus(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'suspension_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $activate = !$member->is_active;

        $member->forceFill([
            'is_active' => $activate,
            'suspended_at' => $activate ? null : now(),
            'suspension_reason' => $activate
                ? null
                : (filled($validated['suspension_reason'] ?? null)
                    ? trim((string) $validated['suspension_reason'])
                    : 'Yönetici işlemi ile askıya alındı.'),
        ])->save();

        return back()->with(
            'success',
            $activate ? 'Üyelik yeniden aktifleştirildi.' : 'Üyelik askıya alındı.'
        );
    }

    public function document(Member $member)
    {
        abort_unless($member->hasDocument() && $member->documentExists(), 404);

        return Storage::disk($member->documentDisk())->response(
            (string) $member->filepath,
            $member->documentName(),
            [
                'Content-Type' => $member->file_mime_type ?: 'application/octet-stream',
            ]
        );
    }

    public function downloadDocument(Member $member)
    {
        abort_unless($member->hasDocument() && $member->documentExists(), 404);

        return Storage::disk($member->documentDisk())->download(
            (string) $member->filepath,
            $member->documentName()
        );
    }

    public function destroy(Member $member): RedirectResponse
    {
        $hasUpcomingAppointment = $member->appointments()
            ->where('status', Appointment::STATUS_BOOKED)
            ->where('end_at', '>=', now())
            ->exists();

        if ($hasUpcomingAppointment) {
            return back()->withErrors([
                'error' => 'Aktif veya yaklaşan randevusu bulunan üyelikler silinemez. Önce randevu sürecini kapatın.',
            ]);
        }

        DB::transaction(function () use ($member) {
            if ($member->hasDocument()) {
                $this->documentService->delete($member);
            }

            $member->forceFill([
                'is_active' => false,
                'suspended_at' => $member->suspended_at ?: now(),
                'suspension_reason' => $member->suspension_reason ?: 'Üyelik silinmeden önce pasife alındı.',
            ])->save();

            $member->delete();
        });

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Üyelik kaydı silindi.');
    }
}
