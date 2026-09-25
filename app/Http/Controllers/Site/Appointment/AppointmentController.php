<?php

namespace App\Http\Controllers\Site\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentMeetingMethod;
use App\Services\Appointment\AppointmentService;
use App\Services\Appointment\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $providers = $this->publicProviders();

        $meetingMethods = AppointmentMeetingMethod::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return view('site.appointments.index', compact(
            'member',
            'providers',
            'meetingMethods',
            'activeAppointment'
        ));
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'string'],
            'date' => ['required', 'date'],
        ]);

        if ($data['provider_id'] === 'any') {
            return $this->availableSlotsForAnyProvider(Carbon::parse($data['date']));
        }

        abort_unless(ctype_digit($data['provider_id']), 422, 'Uzman seçimi geçersiz.');
        $this->assertPublicProvider((int) $data['provider_id']);

        return $this->availabilityService->getAvailableStartsForDate(
            (int) $data['provider_id'],
            Carbon::parse($data['date']),
            1
        );
    }

    public function nearest(Request $request)
    {
        $data = $request->validate([
            'blocks' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        return response()->json([
            'slot' => $this->nearestAvailableSlot((int) ($data['blocks'] ?? 1)),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $user = auth('member')->user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member login gerekli.',
                ], 401);
            }

            $data = $request->validate([
                'provider_id' => ['required', 'regex:/^(any|[1-9][0-9]*)$/'],
                'meeting_method_id' => [
                    'required',
                    'integer',
                    Rule::exists('appointment_meeting_methods', 'id')
                        ->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
                ],
                'start_at' => ['required'],
                'blocks' => ['required', 'integer', 'min:1', 'max:4'],
                'notes_member' => ['nullable', 'string', 'max:2000'],
                'support_topic' => ['required', 'string', Rule::in(Appointment::SUPPORT_TOPICS)],
            ]);
            $providerId = $this->resolveProviderId(
                $data['provider_id'],
                Carbon::parse($data['start_at']),
                (int) $data['blocks']
            );

            $appointment = $this->appointmentService->create([
                'provider_id' => $providerId,
                'member_id' => $user->id,
                'meeting_method_id' => $data['meeting_method_id'],
                'start_at' => $data['start_at'],
                'blocks' => $data['blocks'],
                'notes_member' => filled($data['notes_member'] ?? null) ? trim($data['notes_member']) : null,
                'support_topic' => $data['support_topic'],
            ], null);

            return response()->json([
                'success' => true,
                'id' => $appointment->id,
                'provider_id' => $appointment->provider_id,
            ]);
        } catch (ValidationException $e) {
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
            'provider_id' => ['required', 'string'],
            'month' => ['required', 'date'],
        ]);

        if ($data['provider_id'] === 'any') {
            return $this->calendarForAnyProvider(
                Carbon::parse($data['month'])->startOfMonth(),
                Carbon::parse($data['month'])->endOfMonth(),
            );
        }

        abort_unless(ctype_digit($data['provider_id']), 422, 'Uzman seçimi geçersiz.');
        $this->assertPublicProvider((int) $data['provider_id']);

        $start = Carbon::parse($data['month'])->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $this->availabilityService->getCalendarAvailability(
            (int) $data['provider_id'],
            $start,
            $end
        );
    }

    public function cancel($id)
    {
        try {
            $member = auth('member')->user();

            if (! $member) {
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
        } catch (ValidationException $e) {
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

            if (! $member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member login gerekli.',
                ], 401);
            }

            $data = $request->validate([
                'provider_id' => ['required', 'regex:/^(any|[1-9][0-9]*)$/'],
                'meeting_method_id' => [
                    'required',
                    'integer',
                    Rule::exists('appointment_meeting_methods', 'id')
                        ->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
                ],
                'start_at' => ['required'],
                'blocks' => ['required', 'integer', 'min:1', 'max:4'],
                'notes_member' => ['nullable', 'string', 'max:2000'],
                'support_topic' => ['required', 'string', Rule::in(Appointment::SUPPORT_TOPICS)],
            ]);
            $data['notes_member'] = filled($data['notes_member'] ?? null)
                ? trim($data['notes_member'])
                : null;

            $appointment = Appointment::findOrFail($id);
            $data['provider_id'] = $this->resolveProviderId(
                $data['provider_id'],
                Carbon::parse($data['start_at']),
                (int) $data['blocks'],
                $appointment->id
            );

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
        } catch (ValidationException $e) {
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
            throw ValidationException::withMessages([
                'provider_id' => 'Seçilen uzman kullanılamıyor.',
            ]);
        }
    }

    private function publicProviders(): Collection
    {
        return User::query()
            ->visibleTo($this->adminViewer())
            ->whereHas('roles', fn ($q) => $q->where('slug', 'provider'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function availableSlotsForAnyProvider(Carbon $date, int $blocks = 1): array
    {
        return $this->publicProviders()
            ->flatMap(function (User $provider) use ($date, $blocks): array {
                return collect($this->availabilityService->getAvailableStartsForDate((int) $provider->id, $date, $blocks))
                    ->map(fn (array $slot): array => $slot + [
                        'provider_id' => $provider->id,
                        'provider_name' => $provider->name,
                    ])
                    ->all();
            })
            ->sortBy('start_at')
            ->unique('start_at')
            ->values()
            ->all();
    }

    private function nearestAvailableSlot(int $blocks = 1, int $searchDays = 90): ?array
    {
        $now = Carbon::now(config('app.timezone'))->seconds(0);
        $lastDay = $now->copy()->addDays($searchDays)->endOfDay();
        $month = $now->copy()->startOfMonth();

        while ($month->lte($lastDay)) {
            $rangeStart = $month->isSameMonth($now)
                ? $now->copy()->startOfDay()
                : $month->copy()->startOfMonth();
            $rangeEnd = $month->copy()->endOfMonth()->min($lastDay);

            foreach ($this->calendarForAnyProvider($rangeStart, $rangeEnd) as $date => $availability) {
                if (! ($availability['has_availability'] ?? false)) {
                    continue;
                }

                $slot = collect($this->availableSlotsForAnyProvider(Carbon::parse($date), $blocks))
                    ->first(fn (array $candidate): bool => Carbon::parse($candidate['start_at'])->gt($now));

                if ($slot) {
                    return $slot;
                }
            }

            $month->addMonthNoOverflow()->startOfMonth();
        }

        return null;
    }

    private function resolveProviderId(
        string $providerChoice,
        Carbon $startAt,
        int $blocks,
        ?int $ignoreAppointmentId = null
    ): int {
        if ($providerChoice !== 'any') {
            if (! ctype_digit($providerChoice)) {
                throw ValidationException::withMessages([
                    'provider_id' => 'Uzman seçimi geçersiz.',
                ]);
            }

            $providerId = (int) $providerChoice;
            $this->assertPublicProvider($providerId);

            return $providerId;
        }

        foreach ($this->publicProviders() as $provider) {
            try {
                $this->availabilityService->assertProviderAvailable(
                    (int) $provider->id,
                    $startAt,
                    $blocks,
                    $ignoreAppointmentId
                );

                return (int) $provider->id;
            } catch (ValidationException) {
                // Aynı saatte uygun olan bir sonraki uzmanı dene.
            }
        }

        throw ValidationException::withMessages([
            'provider_id' => 'Seçilen saatte uygun uzman kalmadı. En yakın zamanı yeniden seçin.',
        ]);
    }

    private function calendarForAnyProvider(Carbon $start, Carbon $end): array
    {
        $days = [];

        foreach ($this->publicProviders() as $provider) {
            foreach ($this->availabilityService->getCalendarAvailability((int) $provider->id, $start, $end) as $date => $availability) {
                $days[$date] ??= ['has_availability' => false, 'free_count' => 0];
                $days[$date]['has_availability'] = $days[$date]['has_availability'] || (bool) ($availability['has_availability'] ?? false);
                $days[$date]['free_count'] += (int) ($availability['free_count'] ?? 0);
            }
        }

        ksort($days);

        return $days;
    }

    private function adminViewer(): ?User
    {
        $viewer = auth('web')->user();

        return $viewer instanceof User ? $viewer : null;
    }
}
