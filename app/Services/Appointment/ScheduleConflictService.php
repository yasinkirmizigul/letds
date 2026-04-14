<?php

namespace App\Services\Appointment;

use App\Models\Appointment\Appointment;
use App\Models\Appointment\ProviderTimeOff;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ScheduleConflictService
{
    public function assertNoAppointmentOverlap(
        int $providerId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $ignoreAppointmentId = null,
        string $message = 'Bu zaman aralığında aktif randevu var.'
    ): void {
        if ($this->hasAppointmentOverlap($providerId, $startAt, $endAt, $ignoreAppointmentId)) {
            throw ValidationException::withMessages([
                'provider_id' => $message,
            ]);
        }
    }

    public function assertNoTimeOffOverlap(
        int $providerId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $ignoreTimeOffId = null,
        string $message = 'Seçilen zaman aralığı provider blokajı ile çakışıyor.'
    ): void {
        if ($this->hasTimeOffOverlap($providerId, $startAt, $endAt, $ignoreTimeOffId)) {
            throw ValidationException::withMessages([
                'provider_id' => $message,
            ]);
        }
    }

    public function hasAppointmentOverlap(
        int $providerId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $ignoreAppointmentId = null
    ): bool {
        return Appointment::query()
            ->where('provider_id', $providerId)
            ->where('status', Appointment::STATUS_BOOKED)
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->where(function ($q) use ($startAt, $endAt) {
                $q->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            })
            ->exists();
    }

    public function hasTimeOffOverlap(
        int $providerId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $ignoreTimeOffId = null
    ): bool {
        return ProviderTimeOff::query()
            ->where('provider_id', $providerId)
            ->when($ignoreTimeOffId, fn ($q) => $q->where('id', '!=', $ignoreTimeOffId))
            ->where(function ($q) use ($startAt, $endAt) {
                $q->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            })
            ->exists();
    }
}
