<?php

namespace App\Http\Controllers\Admin\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\ProviderTimeOff;
use App\Services\Appointment\AppointmentService;
use App\Services\Appointment\ScheduleConflictService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentCalendarController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService,
        protected ScheduleConflictService $scheduleConflictService
    ) {
    }

    public function index(Request $request)
    {
        $providers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('slug', ['provider', 'admin', 'superadmin']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'title']);

        return view('admin.pages.appointments.calendar', [
            'pageTitle' => 'Randevular',
        ], compact('providers'));
    }

    public function events(Request $request)
    {
        $providerId = $request->integer('provider_id');
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $appointmentsQuery = Appointment::query()
            ->with([
                'member:id,name,surname',
                'provider:id,name,title',
            ])
            ->where('status', Appointment::STATUS_BOOKED);

        if ($providerId) {
            $appointmentsQuery->where('provider_id', $providerId);
        }

        if ($from !== '') {
            $appointmentsQuery->where('start_at', '>=', Carbon::parse($from, 'Europe/Istanbul'));
        }

        if ($to !== '') {
            $appointmentsQuery->where('start_at', '<', Carbon::parse($to, 'Europe/Istanbul'));
        }

        $appointmentEvents = $appointmentsQuery
            ->orderBy('start_at')
            ->get()
            ->map(function (Appointment $appointment) {
                $memberName = trim(
                    ($appointment->member?->name ?? '') . ' ' . ($appointment->member?->surname ?? '')
                );

                $color = $this->appointmentEventColor($appointment->status);

                return [
                    'id' => 'appointment_' . $appointment->id,
                    'title' => $memberName !== '' ? $memberName : 'Üye',
                    'start' => $appointment->start_at?->toIso8601String(),
                    'end' => $appointment->end_at?->toIso8601String(),
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                    'extendedProps' => [
                        'entity_type' => 'appointment',
                        'entity_id' => $appointment->id,
                        'provider_id' => $appointment->provider_id,
                        'provider_name' => $appointment->provider?->name,
                        'provider_title' => $appointment->provider?->title,
                        'member_id' => $appointment->member_id,
                        'member_name' => $memberName,
                        'status' => $appointment->status,
                        'status_label' => $color['label'],
                        'blocks' => $appointment->blocks,
                        'parent_id' => $appointment->parent_id,
                        'is_transferred' => !is_null($appointment->parent_id),
                    ],
                ];
            });

        $timeOffQuery = ProviderTimeOff::query()
            ->with(['provider:id,name,title']);

        if ($providerId) {
            $timeOffQuery->where('provider_id', $providerId);
        }

        if ($from !== '' && $to !== '') {
            $fromAt = Carbon::parse($from, 'Europe/Istanbul');
            $toAt = Carbon::parse($to, 'Europe/Istanbul');

            $timeOffQuery->where('start_at', '<', $toAt)
                ->where('end_at', '>', $fromAt);
        }

        $timeOffEvents = $timeOffQuery
            ->orderBy('start_at')
            ->get()
            ->map(function (ProviderTimeOff $timeOff) {
                $color = $this->blockEventColor($timeOff->block_type ?? 'manual');

                return [
                    'id' => 'timeoff_' . $timeOff->id,
                    'title' => $timeOff->reason ?: $color['label'],
                    'start' => $timeOff->start_at?->toIso8601String(),
                    'end' => $timeOff->end_at?->toIso8601String(),
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                    'extendedProps' => [
                        'entity_type' => 'time_off',
                        'entity_id' => $timeOff->id,
                        'provider_id' => $timeOff->provider_id,
                        'provider_name' => $timeOff->provider?->name,
                        'provider_title' => $timeOff->provider?->title,
                        'member_id' => null,
                        'member_name' => null,
                        'status' => 'blocked',
                        'status_label' => $color['label'],
                        'blocks' => null,
                        'reason' => $timeOff->reason,
                        'block_type' => $timeOff->block_type,
                    ],
                ];
            });

        return response()->json(
            $appointmentEvents
                ->concat($timeOffEvents)
                ->values()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:users,id'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'start_at' => ['required', 'date'],
            'blocks' => ['required', 'integer', 'min:1', 'max:6'],
            'notes_internal' => ['nullable', 'string'],
        ]);

        $appointment = $this->appointmentService->create($data, auth()->id());

        return response()->json([
            'message' => 'Randevu oluşturuldu.',
            'data' => $appointment,
        ], 201);
    }

    public function transfer(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'new_provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'new_start_at' => ['required', 'date'],
            'blocks' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        $newAppointment = $this->appointmentService->transfer(
            $appointment,
            $data,
            auth()->user()
        );

        return response()->json([
            'message' => 'Randevu taşındı.',
            'data' => $newAppointment,
        ]);
    }

    public function resize(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'blocks' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        $updated = $this->appointmentService->resize(
            $appointment,
            (int) $data['blocks'],
            auth()->user()
        );

        return response()->json([
            'message' => 'Randevu süresi güncellendi.',
            'data' => $updated,
        ]);
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $cancelled = $this->appointmentService->cancelByProvider(
            $appointment,
            $data['reason'] ?? null,
            auth()->user()
        );

        return response()->json([
            'message' => 'Randevu iptal edildi.',
            'data' => $cancelled,
        ]);
    }

    public function history(Appointment $appointment)
    {
        $rootId = $appointment->parent_id ?: $appointment->id;

        $items = Appointment::query()
            ->with([
                'member:id,name,surname',
                'provider:id,name,title',
            ])
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)
                    ->orWhere('parent_id', $rootId);
            })
            ->orderBy('start_at')
            ->get()
            ->map(function (Appointment $item) {
                $memberName = trim(
                    ($item->member?->name ?? '') . ' ' . ($item->member?->surname ?? '')
                );

                return [
                    'id' => $item->id,
                    'parent_id' => $item->parent_id,
                    'status' => $item->status,
                    'start_at' => $item->start_at?->format('d.m.Y H:i'),
                    'end_at' => $item->end_at?->format('d.m.Y H:i'),
                    'provider_name' => $item->provider?->name,
                    'member_name' => $memberName,
                    'is_current' => $item->status === Appointment::STATUS_BOOKED,
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function storeBlock(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:users,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'reason' => ['nullable', 'string', 'max:255'],
            'block_type' => ['required', 'string', 'in:manual,break,meeting,off'],
        ]);

        $providerId = (int) $data['provider_id'];
        $startAt = Carbon::parse($data['start_at'], 'Europe/Istanbul')->seconds(0);
        $endAt = Carbon::parse($data['end_at'], 'Europe/Istanbul')->seconds(0);

        try {
            $this->scheduleConflictService->assertNoAppointmentOverlap(
                $providerId,
                $startAt,
                $endAt,
                null,
                'Bu zaman aralığında aktif randevu var.'
            );

            $this->scheduleConflictService->assertNoTimeOffOverlap(
                $providerId,
                $startAt,
                $endAt,
                null,
                'Bu zaman aralığında zaten blokaj var.'
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Çakışma tespit edildi.',
                'errors' => $e->errors(),
            ], 422);
        }

        $block = ProviderTimeOff::query()->create([
            'provider_id' => $providerId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'reason' => $data['reason'] ?: $this->blockEventColor($data['block_type'])['label'],
            'block_type' => $data['block_type'],
        ]);

        return response()->json([
            'message' => 'Takvim blokajı oluşturuldu.',
            'data' => $block->fresh(),
        ], 201);
    }

    public function moveBlock(Request $request, ProviderTimeOff $timeOff)
    {
        $data = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $startAt = Carbon::parse($data['start_at'], 'Europe/Istanbul')->seconds(0);
        $endAt = Carbon::parse($data['end_at'], 'Europe/Istanbul')->seconds(0);

        try {
            $this->scheduleConflictService->assertNoAppointmentOverlap(
                (int) $timeOff->provider_id,
                $startAt,
                $endAt,
                null,
                'Bu zaman aralığında aktif randevu var. Blokaj taşınamaz.'
            );

            $this->scheduleConflictService->assertNoTimeOffOverlap(
                (int) $timeOff->provider_id,
                $startAt,
                $endAt,
                (int) $timeOff->id,
                'Bu zaman aralığında başka bir blokaj var. Blokaj taşınamaz.'
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Çakışma tespit edildi.',
                'errors' => $e->errors(),
            ], 422);
        }

        $timeOff->update([
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        return response()->json([
            'message' => 'Blokaj zamanı güncellendi.',
            'data' => $timeOff->fresh(),
        ]);
    }

    public function resizeBlock(Request $request, ProviderTimeOff $timeOff)
    {
        $data = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $startAt = Carbon::parse($data['start_at'], 'Europe/Istanbul')->seconds(0);
        $endAt = Carbon::parse($data['end_at'], 'Europe/Istanbul')->seconds(0);

        try {
            $this->scheduleConflictService->assertNoAppointmentOverlap(
                (int) $timeOff->provider_id,
                $startAt,
                $endAt,
                null,
                'Bu zaman aralığında aktif randevu var. Blokaj süresi değiştirilemez.'
            );

            $this->scheduleConflictService->assertNoTimeOffOverlap(
                (int) $timeOff->provider_id,
                $startAt,
                $endAt,
                (int) $timeOff->id,
                'Bu zaman aralığında başka bir blokaj var. Blokaj süresi değiştirilemez.'
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Çakışma tespit edildi.',
                'errors' => $e->errors(),
            ], 422);
        }

        $timeOff->update([
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        return response()->json([
            'message' => 'Blokaj süresi güncellendi.',
            'data' => $timeOff->fresh(),
        ]);
    }

    public function deleteBlock(ProviderTimeOff $timeOff)
    {
        $timeOff->delete();

        return response()->json([
            'message' => 'Blokaj silindi.',
        ]);
    }

    public function updateBlock(Request $request, ProviderTimeOff $timeOff)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'block_type' => ['required', 'string', 'in:manual,break,meeting,off'],
        ]);

        $timeOff->update([
            'reason' => $data['reason'] ?: $this->blockEventColor($data['block_type'])['label'],
            'block_type' => $data['block_type'],
        ]);

        return response()->json([
            'message' => 'Blokaj güncellendi.',
            'data' => $timeOff->fresh(),
        ]);
    }

    protected function appointmentEventColor(string $status): array
    {
        return match ($status) {
            Appointment::STATUS_BOOKED => [
                'bg' => '#2563eb',
                'border' => '#1d4ed8',
                'text' => '#ffffff',
                'label' => 'Randevu',
            ],
            Appointment::STATUS_TRANSFERRED => [
                'bg' => '#6b7280',
                'border' => '#4b5563',
                'text' => '#ffffff',
                'label' => 'Taşındı',
            ],
            Appointment::STATUS_CANCELLED_BY_PROVIDER => [
                'bg' => '#dc2626',
                'border' => '#b91c1c',
                'text' => '#ffffff',
                'label' => 'Provider İptal',
            ],
            Appointment::STATUS_CANCELLED_BY_MEMBER => [
                'bg' => '#d97706',
                'border' => '#b45309',
                'text' => '#ffffff',
                'label' => 'Üye İptal',
            ],
            Appointment::STATUS_COMPLETED => [
                'bg' => '#16a34a',
                'border' => '#15803d',
                'text' => '#ffffff',
                'label' => 'Tamamlandı',
            ],
            Appointment::STATUS_NO_SHOW => [
                'bg' => '#7c3aed',
                'border' => '#6d28d9',
                'text' => '#ffffff',
                'label' => 'Gelmedi',
            ],
            default => [
                'bg' => '#374151',
                'border' => '#1f2937',
                'text' => '#ffffff',
                'label' => 'Randevu',
            ],
        };
    }

    protected function blockEventColor(string $type): array
    {
        return match ($type) {
            'break' => [
                'bg' => '#16a34a',
                'border' => '#15803d',
                'text' => '#ffffff',
                'label' => 'Mola',
            ],
            'meeting' => [
                'bg' => '#7c3aed',
                'border' => '#6d28d9',
                'text' => '#ffffff',
                'label' => 'Toplantı',
            ],
            'off' => [
                'bg' => '#dc2626',
                'border' => '#b91c1c',
                'text' => '#ffffff',
                'label' => 'İzin',
            ],
            default => [
                'bg' => '#d97706',
                'border' => '#b45309',
                'text' => '#ffffff',
                'label' => 'Kapalı',
            ],
        };
    }

    public function showBlock(ProviderTimeOff $timeOff)
    {
        return response()->json([
            'id' => $timeOff->id,
            'provider_id' => $timeOff->provider_id,
            'start_at' => $timeOff->start_at?->toIso8601String(),
            'end_at' => $timeOff->end_at?->toIso8601String(),
            'reason' => $timeOff->reason,
            'block_type' => $timeOff->block_type ?: 'manual',
        ]);
    }

    public function show(Appointment $appointment)
    {
        $appointment->load([
            'member:id,name,surname',
            'provider:id,name,title',
            'parent:id,start_at,end_at,status',
        ]);

        $memberName = trim(
            ($appointment->member?->name ?? '') . ' ' . ($appointment->member?->surname ?? '')
        );

        return response()->json([
            'id' => $appointment->id,
            'provider_id' => $appointment->provider_id,
            'provider_name' => $appointment->provider?->name,
            'provider_title' => $appointment->provider?->title,
            'member_id' => $appointment->member_id,
            'member_name' => $memberName,
            'start_at' => $appointment->start_at?->toIso8601String(),
            'end_at' => $appointment->end_at?->toIso8601String(),
            'blocks' => $appointment->blocks,
            'status' => $appointment->status,
            'status_label' => $this->appointmentEventColor($appointment->status)['label'] ?? $appointment->status,
            'notes_internal' => $appointment->notes_internal,
            'cancel_reason' => $appointment->cancel_reason,
            'parent_id' => $appointment->parent_id,
            'is_transferred' => !is_null($appointment->parent_id),
        ]);
    }
}
