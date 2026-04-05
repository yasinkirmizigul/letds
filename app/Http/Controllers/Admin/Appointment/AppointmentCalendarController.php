<?php

namespace App\Http\Controllers\Admin\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\ProviderTimeOff;
use App\Services\Appointment\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentCalendarController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    public function index(Request $request)
    {
        $providers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('slug', ['provider', 'admin', 'superadmin']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'title']);

        return view('admin.pages.appointments.calendar',[
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

                return [
                    'id' => 'appointment_' . $appointment->id,
                    'title' => $memberName !== '' ? $memberName : 'Üye',
                    'start' => $appointment->start_at?->toIso8601String(),
                    'end' => $appointment->end_at?->toIso8601String(),
                    'backgroundColor' => '#0d6efd',
                    'borderColor' => '#0d6efd',
                    'extendedProps' => [
                        'entity_type' => 'appointment',
                        'entity_id' => $appointment->id,
                        'provider_id' => $appointment->provider_id,
                        'provider_name' => $appointment->provider?->name,
                        'provider_title' => $appointment->provider?->title,
                        'member_id' => $appointment->member_id,
                        'member_name' => $memberName,
                        'status' => $appointment->status,
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

        if ($from !== '') {
            $timeOffQuery->where('start_at', '<', Carbon::parse($to, 'Europe/Istanbul'));
        }

        if ($to !== '') {
            $timeOffQuery->where('end_at', '>', Carbon::parse($from, 'Europe/Istanbul'));
        }

        $timeOffEvents = $timeOffQuery
            ->orderBy('start_at')
            ->get()
            ->map(function (ProviderTimeOff $timeOff) {
                return [
                    'id' => 'timeoff_' . $timeOff->id,
                    'title' => $timeOff->reason ?: 'Kapalı',
                    'start' => $timeOff->start_at?->toIso8601String(),
                    'end' => $timeOff->end_at?->toIso8601String(),
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                    'textColor' => '#111827',
                    'extendedProps' => [
                        'entity_type' => 'time_off',
                        'entity_id' => $timeOff->id,
                        'provider_id' => $timeOff->provider_id,
                        'provider_name' => $timeOff->provider?->name,
                        'provider_title' => $timeOff->provider?->title,
                        'member_id' => null,
                        'member_name' => null,
                        'status' => 'blocked',
                        'blocks' => null,
                        'reason' => $timeOff->reason,
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
        ]);

        $block = ProviderTimeOff::query()->create([
            'provider_id' => (int) $data['provider_id'],
            'start_at' => Carbon::parse($data['start_at'], 'Europe/Istanbul')->seconds(0),
            'end_at' => Carbon::parse($data['end_at'], 'Europe/Istanbul')->seconds(0),
            'reason' => $data['reason'] ?: 'Kapalı',
        ]);

        return response()->json([
            'message' => 'Takvim blokajı oluşturuldu.',
            'data' => $block,
        ], 201);
    }

    public function moveBlock(Request $request, ProviderTimeOff $timeOff)
    {
        $data = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $timeOff->update([
            'start_at' => Carbon::parse($data['start_at'], 'Europe/Istanbul')->seconds(0),
            'end_at' => Carbon::parse($data['end_at'], 'Europe/Istanbul')->seconds(0),
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

        $timeOff->update([
            'start_at' => Carbon::parse($data['start_at'], 'Europe/Istanbul')->seconds(0),
            'end_at' => Carbon::parse($data['end_at'], 'Europe/Istanbul')->seconds(0),
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
}
