<?php

namespace App\Services\Appointment;

use App\Models\Appointment\Appointment;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function create(array $data, ?int $actorUserId = null): Appointment
    {
        return DB::transaction(function () use ($data, $actorUserId) {
            $memberId = (int) $data['member_id'];
            $providerId = (int) $data['provider_id'];
            $startAt = Carbon::parse($data['start_at'])->seconds(0);
            $blocks = max(1, (int) $data['blocks']);
            $endAt = (clone $startAt)->addMinutes($blocks * 30);

            $this->assertMemberHasNoActiveBooking($memberId);

            $appointment = Appointment::create([
                'provider_id' => $providerId,
                'member_id' => $memberId,
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

    public function transfer(Appointment $appointment, array $data, ?int $actorUserId = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $data, $actorUserId) {
            if ($appointment->status !== Appointment::STATUS_BOOKED) {
                throw ValidationException::withMessages([
                    'appointment' => 'Sadece aktif randevu taşınabilir.',
                ]);
            }

            $newProviderId = (int) ($data['new_provider_id'] ?? $appointment->provider_id);
            $newStartAt = Carbon::parse($data['new_start_at'])->seconds(0);
            $blocks = max(1, (int) $data['blocks']);
            $newEndAt = (clone $newStartAt)->addMinutes($blocks * 30);

            $appointment->update([
                'status' => Appointment::STATUS_TRANSFERRED,
            ]);

            $new = Appointment::create([
                'provider_id' => $newProviderId,
                'member_id' => $appointment->member_id,
                'start_at' => $newStartAt,
                'end_at' => $newEndAt,
                'blocks' => $blocks,
                'status' => Appointment::STATUS_BOOKED,
                'notes_internal' => $appointment->notes_internal,
                'created_by_user_id' => $actorUserId,
                'parent_id' => $appointment->id,
            ]);

            $this->writeSlots($new);
            $appointment->slots()->delete();

            return $new->fresh(['member', 'provider']);
        });
    }

    public function resize(Appointment $appointment, int $blocks): Appointment
    {
        return DB::transaction(function () use ($appointment, $blocks) {
            if ($appointment->status !== Appointment::STATUS_BOOKED) {
                throw ValidationException::withMessages([
                    'appointment' => 'Sadece aktif randevunun süresi değiştirilebilir.',
                ]);
            }

            $blocks = max(1, $blocks);
            $appointment->slots()->delete();

            $appointment->update([
                'blocks' => $blocks,
                'end_at' => (clone $appointment->start_at)->addMinutes($blocks * 30),
            ]);

            $this->writeSlots($appointment);

            return $appointment->fresh(['member', 'provider']);
        });
    }

    public function cancelByProvider(Appointment $appointment, ?string $reason, ?int $actorUserId = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $actorUserId) {
            if ($appointment->status !== Appointment::STATUS_BOOKED) {
                throw ValidationException::withMessages([
                    'appointment' => 'Sadece aktif randevu iptal edilebilir.',
                ]);
            }

            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED_BY_PROVIDER,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'cancelled_by_user_id' => $actorUserId,
            ]);

            $appointment->slots()->delete();

            return $appointment->fresh(['member', 'provider']);
        });
    }

    protected function assertMemberHasNoActiveBooking(int $memberId): void
    {
        $exists = Appointment::query()
            ->where('member_id', $memberId)
            ->where('status', Appointment::STATUS_BOOKED)
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'member_id' => 'Bu üyenin zaten aktif bir randevusu var.',
            ]);
        }
    }

    protected function writeSlots(Appointment $appointment): void
    {
        $slotAt = (clone $appointment->start_at)->seconds(0);

        try {
            for ($i = 0; $i < $appointment->blocks; $i++) {
                $appointment->slots()->create([
                    'provider_id' => $appointment->provider_id,
                    'slot_start_at' => $slotAt,
                ]);

                $slotAt = (clone $slotAt)->addMinutes(30);
            }
        } catch (QueryException $e) {
            if ($this->isDuplicateSlotError($e)) {
                throw ValidationException::withMessages([
                    'slot' => 'Seçilen saat aralığı bu kişi için dolu.',
                ]);
            }

            throw $e;
        }
    }

    protected function isDuplicateSlotError(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        return $sqlState === '23000' && (int) $driverCode === 1062;
    }
}
