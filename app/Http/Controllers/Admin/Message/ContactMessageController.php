<?php

namespace App\Http\Controllers\Admin\Message;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Models\ContactMessage;
use App\Services\Admin\AdminNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactMessageController extends Controller
{
    public function index(): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user?->canAccessAdmin(), 403);

        $messages = ContactMessage::query()
            ->with([
                'recipient:id,name,email',
                'assignedUser:id,name,email',
                'member:id,name,surname,email,phone',
            ])
            ->visibleToUser($user)
            ->orderByDesc('created_at')
            ->get();

        $recipientOptions = $user->isSuperAdmin()
            ? User::query()
                ->adminAccessible()
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $assigneeOptions = User::query()
            ->adminAccessible()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.pages.messages.index', [
            'pageTitle' => 'Mesajlar',
            'messages' => $messages,
            'recipientOptions' => $recipientOptions,
            'assigneeOptions' => $assigneeOptions,
            'priorityOptions' => ContactMessage::priorityOptionsSorted(),
            'statusOptions' => ContactMessage::statusOptionsSorted(),
            'stats' => [
                'total' => $messages->count(),
                'unread' => $messages->whereNull('read_at')->count(),
                'open' => $messages->reject(fn (ContactMessage $message) => $message->isClosed())->count(),
                'overdue' => $messages->filter(fn (ContactMessage $message) => $message->isOverdue())->count(),
                'highPriority' => $messages->filter(fn (ContactMessage $message) => in_array($message->priority, [
                    ContactMessage::PRIORITY_HIGH,
                    ContactMessage::PRIORITY_URGENT,
                ], true))->count(),
                'guest' => $messages->where('sender_type', ContactMessage::SENDER_TYPE_GUEST)->count(),
            ],
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    public function show(ContactMessage $contactMessage): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user?->canAccessAdmin(), 403);
        abort_unless($contactMessage->isVisibleToUser($user), 403);

        $contactMessage->load([
            'recipient:id,name,email',
            'assignedUser:id,name,email',
            'closedBy:id,name,email',
            'member:id,name,surname,email,phone',
        ]);

        if (!$contactMessage->isRead()) {
            $contactMessage->forceFill([
                'read_at' => now(),
                'last_activity_at' => $contactMessage->last_activity_at ?: now(),
            ])->save();
        }

        return view('admin.pages.messages.show', [
            'pageTitle' => 'Mesaj Detayı',
            'message' => $contactMessage,
            'statusOptions' => ContactMessage::statusOptionsSorted(),
            'assigneeOptions' => User::query()
                ->adminAccessible()
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function updateWorkflow(Request $request, ContactMessage $contactMessage): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless($user?->canAccessAdmin(), 403);
        abort_unless($contactMessage->isVisibleToUser($user), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContactMessage::statusOptions()))],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
            'resolution_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $status = (string) $validated['status'];
        $payload = [
            'status' => $status,
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'tags' => $this->normalizeTags($validated['tags'] ?? null),
            'internal_note' => $validated['internal_note'] ?? null,
            'resolution_note' => $validated['resolution_note'] ?? null,
            'last_activity_at' => now(),
        ];

        if (
            !$contactMessage->first_response_at
            && in_array($status, [
                ContactMessage::STATUS_IN_PROGRESS,
                ContactMessage::STATUS_WAITING,
                ContactMessage::STATUS_RESOLVED,
                ContactMessage::STATUS_CLOSED,
            ], true)
        ) {
            $payload['first_response_at'] = now();
        }

        if (in_array($status, [ContactMessage::STATUS_RESOLVED, ContactMessage::STATUS_CLOSED], true) && !$contactMessage->resolved_at) {
            $payload['resolved_at'] = now();
        }

        if ($status === ContactMessage::STATUS_CLOSED && !$contactMessage->closed_at) {
            $payload['closed_at'] = now();
            $payload['closed_by_user_id'] = $user->id;
        }

        $beforeAssignee = (int) ($contactMessage->assigned_user_id ?? 0);
        $contactMessage->forceFill($payload)->save();

        if ((int) ($payload['assigned_user_id'] ?? 0) > 0 && (int) ($payload['assigned_user_id'] ?? 0) !== $beforeAssignee) {
            $assignee = User::query()->find($payload['assigned_user_id']);
            app(AdminNotificationService::class)->notifyUser($assignee, [
                'type' => 'message',
                'severity' => 'info',
                'title' => 'Mesaj size atandı',
                'body' => $contactMessage->subject,
                'action_label' => 'Mesajı aç',
                'action_url' => route('admin.messages.show', $contactMessage),
                'source_type' => ContactMessage::class,
                'source_id' => $contactMessage->id,
            ]);
        }

        return $this->respond($request, 'Mesaj iş akışı güncellendi.');
    }

    private function normalizeTags(?string $value): array
    {
        return collect(preg_split('/[,;\r\n]+/u', (string) $value))
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique(fn ($tag) => mb_strtolower($tag))
            ->values()
            ->take(12)
            ->all();
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'redirect_url' => url()->previous(),
            ]);
        }

        return back()->with('success', $message);
    }
}
