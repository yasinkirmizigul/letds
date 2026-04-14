<?php

namespace App\Services\Appointment;

use App\Jobs\SendAppointmentUpdatedMailJob;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    protected string $tz = 'Europe/Istanbul';

    public function __construct(
        protected AvailabilityService $availabilityService,
        protected ScheduleConflictService $scheduleConflictService
    ) {
    }

    public function create(array $data, ?int $actorUserId = null): Appointment
    {
        return DB::transaction(function () use ($data, $actorUserId) {
            $startAt = Carbon::parse($data['start_at'], $this->tz)->seconds(0);

            $this->assertStartAtNotInPast($startAt);

            $blocks = max(1, (int) $data['blocks']);
            $providerId = (int) $data['provider_id'];
            $endAt = $this->calculateEndAt($startAt, $blocks);

            $this->assertMemberHasNoActiveBooking((int) $data['member_id']);

            $this->scheduleConflictService->assertNoTimeOffOverlap(
                $providerId,
                $startAt,
                $endAt,
                null,
                'Seçilen zaman aralığı provider blokajı ile çakışıyor. Randevu oluşturulamaz.'
            );

            $this->availabilityService->assertProviderAvailable(
                $providerId,
                $startAt,
                $blocks
            );

            $appointment = Appointment::create([
                'provider_id' => $providerId,
                'member_id' => (int) $data['member_id'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'blocks' => $blocks,
                'status' => Appointment::STATUS_BOOKED,
                'notes_internal' => $data['notes_internal'] ?? null,
                'created_by_user_id' => $actorUserId,
            ]);

            $this->writeSlots($appointment);

            return $appointment->fresh(['member', 'provider']);
        });
    }

    public function transfer(Appointment $appointment, array $data, User $actor): Appointment
    {
        $this->authorizeManage($appointment, $actor);
        $this->assertCanTransition($appointment, Appointment::STATUS_TRANSFERRED);

        $new = DB::transaction(function () use ($appointment, $data, $actor) {
            $newStartAt = Carbon::parse($data['new_start_at'], $this->tz)->seconds(0);
            $this->assertStartAtNotInPast($newStartAt);

            $blocks = max(1, (int) $data['blocks']);
            $newProviderId = (int) ($data['new_provider_id'] ?? $appointment->provider_id);
            $newEndAt = $this->calculateEndAt($newStartAt, $blocks);

            $this->scheduleConflictService->assertNoTimeOffOverlap(
                $newProviderId,
                $newStartAt,
                $newEndAt,
                null,
                'Seçilen yeni zaman aralığı provider blokajı ile çakışıyor. Randevu taşınamaz.'
            );

            $this->availabilityService->assertProviderAvailable(
                $newProviderId,
                $newStartAt,
                $blocks,
                $appointment->id
            );

            $appointment->update([
                'status' => Appointment::STATUS_TRANSFERRED,
            ]);

            $appointment->slots()->delete();

            $new = Appointment::create([
                'provider_id' => $newProviderId,
                'member_id' => $appointment->member_id,
                'start_at' => $newStartAt,
                'end_at' => $newEndAt,
                'blocks' => $blocks,
                'status' => Appointment::STATUS_BOOKED,
                'created_by_user_id' => $actor->id,
                'notes_internal' => $appointment->notes_internal,
                'parent_id' => $appointment->id,
            ]);

            $this->writeSlots($new);

            return $new->fresh(['member', 'provider', 'parent']);
        });

        SendAppointmentUpdatedMailJob::dispatch($new->id, 'transferred');

        return $new;
    }

    public function resize(Appointment $appointment, int $blocks, User $actor): Appointment
    {
        $this->authorizeManage($appointment, $actor);

        $updated = DB::transaction(function () use ($appointment, $blocks) {
            $blocks = max(1, $blocks);

            $startAt = $appointment->start_at->copy()->seconds(0);
            $endAt = $this->calculateEndAt($startAt, $blocks);

            $this->scheduleConflictService->assertNoTimeOffOverlap(
                (int) $appointment->provider_id,
                $startAt,
                $endAt,
                null,
                'Yeni süre provider blokajı ile çakışıyor. Randevu süresi güncellenemez.'
            );

            $this->availabilityService->assertProviderAvailable(
                (int) $appointment->provider_id,
                $startAt,
                $blocks,
                $appointment->id
            );

            $appointment->slots()->delete();

            $appointment->update([
                'blocks' => $blocks,
                'end_at' => $endAt,
            ]);

            $this->writeSlots($appointment);

            return $appointment->fresh(['member', 'provider']);
        });

        SendAppointmentUpdatedMailJob::dispatch($updated->id, 'resized');

        return $updated;
    }

    public function cancelByProvider(Appointment $appointment, ?string $reason, User $actor): Appointment
    {
        $this->authorizeManage($appointment, $actor);
        $this->assertCanTransition($appointment, Appointment::STATUS_CANCELLED_BY_PROVIDER);

        $cancelled = DB::transaction(function () use ($appointment, $reason, $actor) {
            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED_BY_PROVIDER,
                'cancelled_at' => Carbon::now($this->tz),
                'cancel_reason' => $reason,
                'cancelled_by_user_id' => $actor->id,
            ]);

            $appointment->slots()->delete();

            return $appointment->fresh(['member', 'provider', 'parent', 'children']);
        });

        SendAppointmentUpdatedMailJob::dispatch($cancelled->id, 'cancelled');

        return $cancelled;
    }

    public function getActiveForMember(int $memberId): ?Appointment
    {
        return Appointment::query()
            ->where('member_id', $memberId)
            ->where('status', Appointment::STATUS_BOOKED)
            ->where('end_at', '>=', Carbon::now('UTC'))
            ->orderBy('start_at')
            ->first();
    }

    public function cancelByMember(Appointment $appointment, int $memberId): Appointment
    {
        if ((int) $appointment->member_id !== (int) $memberId) {
            throw ValidationException::withMessages([
                'auth' => 'Yetkisiz işlem.',
            ]);
        }

        if ($appointment->start_at->copy()->seconds(0)->lt(Carbon::now($this->tz)->seconds(0))) {
            throw ValidationException::withMessages([
                'status' => 'Geçmiş randevu iptal edilemez.',
            ]);
        }

        $this->assertCanTransition($appointment, Appointment::STATUS_CANCELLED_BY_MEMBER);

        return DB::transaction(function () use ($appointment) {
            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED_BY_MEMBER,
                'cancelled_at' => Carbon::now($this->tz),
            ]);

            $appointment->slots()->delete();

            return $appointment->fresh(['member', 'provider', 'parent', 'children']);
        });
    }

    public function rescheduleByMember(Appointment $appointment, array $data, int $memberId): Appointment
    {
        if ((int) $appointment->member_id !== (int) $memberId) {
            throw ValidationException::withMessages([
                'auth' => 'Yetkisiz işlem.',
            ]);
        }

        if ($appointment->start_at->copy()->seconds(0)->lt(Carbon::now($this->tz)->seconds(0))) {
            throw ValidationException::withMessages([
                'status' => 'Geçmiş randevu yeniden planlanamaz.',
            ]);
        }

        $this->assertCanTransition($appointment, Appointment::STATUS_TRANSFERRED);

        return DB::transaction(function () use ($appointment, $data) {
            $newStartAt = Carbon::parse($data['start_at'], $this->tz)->seconds(0);
            $this->assertStartAtNotInPast($newStartAt);

            $blocks = max(1, (int) ($data['blocks'] ?? $appointment->blocks));
            $newProviderId = (int) ($data['provider_id'] ?? $appointment->provider_id);
            $newEndAt = $this->calculateEndAt($newStartAt, $blocks);

            $this->scheduleConflictService->assertNoTimeOffOverlap(
                $newProviderId,
                $newStartAt,
                $newEndAt,
                null,
                'Seçilen yeni zaman aralığı provider blokajı ile çakışıyor. Randevu yeniden planlanamaz.'
            );

            $this->availabilityService->assertProviderAvailable(
                $newProviderId,
                $newStartAt,
                $blocks,
                $appointment->id
            );

            $appointment->update([
                'status' => Appointment::STATUS_TRANSFERRED,
            ]);

            $appointment->slots()->delete();

            $newAppointment = Appointment::create([
                'provider_id' => $newProviderId,
                'member_id' => $appointment->member_id,
                'start_at' => $newStartAt,
                'end_at' => $newEndAt,
                'blocks' => $blocks,
                'status' => Appointment::STATUS_BOOKED,
                'notes_internal' => $appointment->notes_internal,
                'parent_id' => $appointment->id,
            ]);

            $this->writeSlots($newAppointment);

            return $newAppointment->fresh(['member', 'provider', 'parent']);
        });
    }

    protected function authorizeManage(Appointment $appointment, User $actor): void
    {
        if ($actor->isSuperAdmin() || $actor->hasRole('admin')) {
            return;
        }

        if (!$actor->hasRole('provider') || (int) $appointment->provider_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'auth' => 'Yetkisiz işlem.',
            ]);
        }
    }

    protected function assertMemberHasNoActiveBooking(int $memberId): void
    {
        if (
            Appointment::query()
                ->where('member_id', $memberId)
                ->where('status', Appointment::STATUS_BOOKED)
                ->where('end_at', '>=', Carbon::now('UTC'))
                ->lockForUpdate()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'member_id' => 'Bu üyenin zaten aktif bir randevusu var.',
            ]);
        }
    }

    protected function writeSlots(Appointment $appointment): void
    {
        $slotAt = $appointment->start_at->copy()->seconds(0);

        try {
            for ($i = 0; $i < $appointment->blocks; $i++) {
                $appointment->slots()->create([
                    'provider_id' => $appointment->provider_id,
                    'slot_start_at' => $slotAt,
                ]);

                $slotAt = $slotAt->copy()->addMinutes(30);
            }
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23000') {
                throw ValidationException::withMessages([
                    'slot' => 'Seçilen saat dolu.',
                ]);
            }

            throw $e;
        }
    }

    protected function calculateEndAt(Carbon $startAt, int $blocks): Carbon
    {
        return $startAt->copy()->addMinutes($blocks * 30);
    }

    protected function assertStartAtNotInPast(Carbon $startAt): void
    {
        if ($startAt->lt(Carbon::now($this->tz)->seconds(0))) {
            throw ValidationException::withMessages([
                'start_at' => 'Geçmiş bir saate randevu oluşturulamaz.',
            ]);
        }
    }

    protected function assertCanTransition(Appointment $appointment, string $toStatus): void
    {
        $allowed = [
            Appointment::STATUS_BOOKED => [
                Appointment::STATUS_TRANSFERRED,
                Appointment::STATUS_CANCELLED_BY_PROVIDER,
                Appointment::STATUS_CANCELLED_BY_MEMBER,
                Appointment::STATUS_COMPLETED,
                Appointment::STATUS_NO_SHOW,
            ],
            Appointment::STATUS_TRANSFERRED => [],
            Appointment::STATUS_CANCELLED_BY_PROVIDER => [],
            Appointment::STATUS_CANCELLED_BY_MEMBER => [],
            Appointment::STATUS_COMPLETED => [],
            Appointment::STATUS_NO_SHOW => [],
        ];

        $current = $appointment->status;

        if (!in_array($toStatus, $allowed[$current] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Geçersiz durum geçişi.',
            ]);
        }
    }
}
