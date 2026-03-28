<?php

namespace App\Services\Appointment;

use App\Jobs\SendAppointmentUpdatedMailJob;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Support\DateTimeHelper;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    public function create(array $data, ?int $actorUserId = null): Appointment
    {
        return DB::transaction(function () use ($data, $actorUserId) {

            $startAtUtc = DateTimeHelper::toUtc($data['start_at']);

            if (!$startAtUtc) {
                throw ValidationException::withMessages([
                    'start_at' => 'Geçersiz tarih.',
                ]);
            }

            $startAtUtc = $startAtUtc->seconds(0);
            $blocks = max(1, (int) $data['blocks']);

            $this->assertMemberHasNoActiveBooking((int) $data['member_id']);
            $this->availabilityService->assertProviderAvailable((int) $data['provider_id'], $startAtUtc, $blocks);

            $appointment = Appointment::create([
                'provider_id' => (int) $data['provider_id'],
                'member_id' => (int) $data['member_id'],
                'start_at' => $startAtUtc,
                'end_at' => $startAtUtc->copy()->addMinutes($blocks * 30),
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

        $new = DB::transaction(function () use ($appointment, $data, $actor) {

            $newStartAtUtc = DateTimeHelper::toUtc($data['new_start_at']);

            if (!$newStartAtUtc) {
                throw ValidationException::withMessages([
                    'start_at' => 'Geçersiz tarih.',
                ]);
            }

            $newStartAtUtc = $newStartAtUtc->seconds(0);
            $blocks = max(1, (int) $data['blocks']);

            $newProviderId = (int) ($data['new_provider_id'] ?? $appointment->provider_id);

            $this->availabilityService->assertProviderAvailable(
                $newProviderId,
                $newStartAtUtc,
                $blocks,
                $appointment->id
            );

            $appointment->update(['status' => Appointment::STATUS_TRANSFERRED]);
            $appointment->slots()->delete();

            $new = Appointment::create([
                'provider_id' => $newProviderId,
                'member_id' => $appointment->member_id,
                'start_at' => $newStartAtUtc,
                'end_at' => $newStartAtUtc->copy()->addMinutes($blocks * 30),
                'blocks' => $blocks,
                'status' => Appointment::STATUS_BOOKED,
                'created_by_user_id' => $actor->id,
                'parent_id' => $appointment->id,
            ]);

            $this->writeSlots($new);

            return $new->fresh(['member', 'provider']);
        });

        SendAppointmentUpdatedMailJob::dispatch($new->id, 'transferred');

        return $new;
    }

    public function resize(Appointment $appointment, int $blocks, User $actor): Appointment
    {
        $this->authorizeManage($appointment, $actor);

        $updated = DB::transaction(function () use ($appointment, $blocks) {

            $blocks = max(1, $blocks);

            $this->availabilityService->assertProviderAvailable(
                (int) $appointment->provider_id,
                $appointment->start_at->copy(),
                $blocks,
                $appointment->id
            );

            $appointment->slots()->delete();

            $appointment->update([
                'blocks' => $blocks,
                'end_at' => $appointment->start_at->copy()->addMinutes($blocks * 30),
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

        $cancelled = DB::transaction(function () use ($appointment, $reason, $actor) {

            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED_BY_PROVIDER,
                'cancelled_at' => DateTimeHelper::nowUtc(),
                'cancel_reason' => $reason,
                'cancelled_by_user_id' => $actor->id,
            ]);

            $appointment->slots()->delete();

            return $appointment->fresh(['member', 'provider']);
        });

        SendAppointmentUpdatedMailJob::dispatch($cancelled->id, 'cancelled');

        return $cancelled;
    }

    protected function authorizeManage(Appointment $appointment, User $actor): void
    {
        if ($actor->isSuperAdmin() || $actor->hasRole('admin')) return;

        if (!$actor->hasRole('provider') || (int)$appointment->provider_id !== (int)$actor->id) {
            throw ValidationException::withMessages(['auth' => 'Yetkisiz işlem.']);
        }
    }

    protected function assertMemberHasNoActiveBooking(int $memberId): void
    {
        if (Appointment::query()
            ->where('member_id', $memberId)
            ->where('status', Appointment::STATUS_BOOKED)
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
        $slotAtUtc = $appointment->start_at->copy()->seconds(0);

        try {
            for ($i = 0; $i < $appointment->blocks; $i++) {
                $appointment->slots()->create([
                    'provider_id' => $appointment->provider_id,
                    'slot_start_at' => $slotAtUtc,
                ]);

                $slotAtUtc = $slotAtUtc->copy()->addMinutes(30);
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
}
