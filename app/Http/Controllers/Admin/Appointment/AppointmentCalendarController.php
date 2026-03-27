<?php

namespace App\Http\Controllers\Admin\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Services\Appointment\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        return view('admin.pages.appointments.calendar', compact('providers'));
    }

    public function events(Request $request)
    {
        $providerId = $request->integer('provider_id');
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $query = Appointment::query()
            ->with([
                'member:id,name,surname',
                'provider:id,name,title',
            ])
            ->where('status', Appointment::STATUS_BOOKED);

        if ($providerId) {
            $query->where('provider_id', $providerId);
        }

        if ($from !== '') {
            $query->where('start_at', '>=', Carbon::parse($from));
        }

        if ($to !== '') {
            $query->where('start_at', '<', Carbon::parse($to));
        }

        $events = $query
            ->orderBy('start_at')
            ->get()
            ->map(function (Appointment $appointment) {
                $memberName = trim(
                    ($appointment->member?->name ?? '') . ' ' . ($appointment->member?->surname ?? '')
                );

                return [
                    'id' => (string) $appointment->id,
                    'title' => $memberName !== '' ? $memberName : 'Üye',
                    'start' => $appointment->start_at?->toIso8601String(),
                    'end' => $appointment->end_at?->toIso8601String(),
                    'extendedProps' => [
                        'provider_id' => $appointment->provider_id,
                        'provider_name' => $appointment->provider?->name,
                        'provider_title' => $appointment->provider?->title,
                        'member_id' => $appointment->member_id,
                        'member_name' => $memberName,
                        'status' => $appointment->status,
                        'blocks' => $appointment->blocks,
                    ],
                ];
            })
            ->values();

        return response()->json($events);
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

        $newAppointment = $this->appointmentService->transfer($appointment, $data, auth()->id());

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

        $updated = $this->appointmentService->resize($appointment, (int) $data['blocks']);

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
            auth()->id()
        );

        return response()->json([
            'message' => 'Randevu iptal edildi.',
            'data' => $cancelled,
        ]);
    }
}
