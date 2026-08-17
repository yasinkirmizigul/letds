<?php

namespace App\Http\Controllers\Site\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Services\Appointment\AppointmentService;
use App\Services\Appointment\AvailabilityService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected AppointmentService $appointmentService
    ) {}

    public function index()
    {
        $member = auth('member')->user();

        $activeAppointment = null;

        if ($member) {
            $activeAppointment = $this->appointmentService
                ->getActiveForMember($member->id);
        }

        $providers = User::query()
            ->visibleTo($this->adminViewer())
            ->whereHas('roles', fn($q) => $q->where('slug','provider'))
            ->where('is_active', 1)
            ->get(['id','name']);

        return view('site.appointments.index', compact(
            'providers',
            'activeAppointment'
        ));
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required','integer'],
            'date' => ['required','date'],
        ]);
        $this->assertPublicProvider((int) $data['provider_id']);

        return $this->availabilityService->getAvailableStartsForDate(
            (int)$data['provider_id'],
            \Carbon\Carbon::parse($data['date']),
            1
        );
    }

    public function store(Request $request)
    {
        try {
            $user = auth('member')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member login gerekli.'
                ], 401);
            }

            $data = $request->validate([
                'provider_id' => ['required','integer'],
                'start_at' => ['required'],
                'blocks' => ['required','integer','min:1','max:4'],
            ]);
            $this->assertPublicProvider((int) $data['provider_id']);

            $appointment = $this->appointmentService->create([
                'provider_id' => $data['provider_id'],
                'member_id' => $user->id,
                'start_at' => $data['start_at'],
                'blocks' => $data['blocks'],
            ], null);

            return response()->json([
                'success' => true,
                'id' => $appointment->id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('MEMBER BOOKING ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Randevu oluşturulurken beklenmeyen bir hata oluştu.',
            ], 500);
        }
    }
    public function days(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required','integer'],
            'month' => ['required','date'],
        ]);
        $this->assertPublicProvider((int) $data['provider_id']);

        $start = \Carbon\Carbon::parse($data['month'])->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $this->availabilityService->getCalendarAvailability(
            (int)$data['provider_id'],
            $start,
            $end
        );
    }
    public function cancel($id)
    {
        try {
            $member = auth('member')->user();

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member login gerekli.',
                ], 401);
            }

            $appointment = Appointment::findOrFail($id);

            $this->appointmentService->cancelByMember($appointment, $member->id);

            return response()->json([
                'success' => true,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('MEMBER APPOINTMENT CANCEL ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Randevu iptal edilemedi.',
            ], 500);
        }
    }

    public function reschedule(Request $request, $id)
    {
        try {
            $member = auth('member')->user();

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member login gerekli.',
                ], 401);
            }

            $data = $request->validate([
                'provider_id' => ['required', 'integer'],
                'start_at' => ['required'],
                'blocks' => ['required', 'integer', 'min:1', 'max:4'],
            ]);
            $this->assertPublicProvider((int) $data['provider_id']);

            $appointment = Appointment::findOrFail($id);

            $updatedAppointment = $this->appointmentService->rescheduleByMember(
                $appointment,
                $data,
                $member->id
            );

            return response()->json([
                'success' => true,
                'id' => $updatedAppointment->id,
                'message' => 'Randevu yeniden planlandı.',
                'data' => [
                    'id' => $updatedAppointment->id,
                    'parent_id' => $updatedAppointment->parent_id,
                    'start_at' => $updatedAppointment->start_at?->toIso8601String(),
                    'end_at' => $updatedAppointment->end_at?->toIso8601String(),
                    'provider_id' => $updatedAppointment->provider_id,
                    'status' => $updatedAppointment->status,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('MEMBER APPOINTMENT RESCHEDULE ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Randevu yeniden planlanamadı.',
            ], 500);
        }
    }

    private function assertPublicProvider(int $providerId): void
    {
        $exists = User::query()
            ->visibleTo($this->adminViewer())
            ->whereKey($providerId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'provider'))
            ->exists();

        if (! $exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'provider_id' => 'Seçilen uzman kullanılamıyor.',
            ]);
        }
    }

    private function adminViewer(): ?User
    {
        $viewer = auth('web')->user();

        return $viewer instanceof User ? $viewer : null;
    }
}
