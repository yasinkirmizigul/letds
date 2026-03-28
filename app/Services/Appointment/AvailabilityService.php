<?php

namespace App\Services\Appointment;

use App\Models\Appointment\AppointmentSlot;
use App\Models\Appointment\GlobalBlackout;
use App\Models\Appointment\ProviderTimeOff;
use App\Models\Appointment\ProviderWorkingHour;
use App\Support\DateTimeHelper;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    protected string $tz = DateTimeHelper::APP_TZ;

    public function assertProviderAvailable(
        int $providerId,
        Carbon $startAtUtc,
        int $blocks,
        ?int $ignoreAppointmentId = null
    ): void {
        $startAtUtc = $startAtUtc->copy()->seconds(0);
        $blocks = max(1, $blocks);

        $startAtLocal = $startAtUtc->copy()->setTimezone($this->tz);

        if (!$this->isSlotAligned($startAtLocal)) {
            throw ValidationException::withMessages([
                'start_at' => 'Başlangıç saati 30 dakikalık aralığa uygun olmalı.',
            ]);
        }

        // 🔥 working hour tek query
        $workingHour = ProviderWorkingHour::query()
            ->where('provider_id', $providerId)
            ->where('day_of_week', (int) $startAtLocal->dayOfWeekIso)
            ->where('is_enabled', true)
            ->first();

        if (!$workingHour || !$workingHour->start_time || !$workingHour->end_time) {
            throw ValidationException::withMessages([
                'availability' => 'Seçilen gün bu kişi için kapalı.',
            ]);
        }

        $dayStartLocal = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $startAtLocal->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->start_time),
            $this->tz
        );

        $dayEndLocal = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $startAtLocal->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->end_time),
            $this->tz
        );

        for ($i = 0; $i < $blocks; $i++) {

            $slotStartUtc = $startAtUtc->copy()->addMinutes($i * 30);
            $slotEndUtc = $slotStartUtc->copy()->addMinutes(30);

            $slotStartLocal = $startAtLocal->copy()->addMinutes($i * 30);
            $slotEndLocal = $slotStartLocal->copy()->addMinutes(30);

            // 🔥 working hours LOCAL
            if ($slotStartLocal->lt($dayStartLocal) || $slotEndLocal->gt($dayEndLocal)) {
                throw ValidationException::withMessages([
                    'availability' => 'Seçilen saat çalışma saatleri dışında.',
                ]);
            }

            // 🔥 UTC kontroller
            $this->assertNotInProviderTimeOff($providerId, $slotStartUtc, $slotEndUtc);
            $this->assertNotInGlobalBlackout($slotStartUtc, $slotEndUtc);
            $this->assertSlotNotOccupied($providerId, $slotStartUtc, $ignoreAppointmentId);
        }
    }

    public function getAvailableStartsForDate(int $providerId, Carbon $date, int $blocks = 1): array
    {
        $blocks = max(1, $blocks);

        $dateLocal = $date->copy()->setTimezone($this->tz)->startOfDay();
        $dayOfWeek = (int) $dateLocal->dayOfWeekIso;

        $workingHour = ProviderWorkingHour::query()
            ->where('provider_id', $providerId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_enabled', true)
            ->first();

        if (!$workingHour || !$workingHour->start_time || !$workingHour->end_time) {
            return [];
        }

        $workStartLocal = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $dateLocal->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->start_time),
            $this->tz
        );

        $workEndLocal = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $dateLocal->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->end_time),
            $this->tz
        );

        $results = [];
        $cursorLocal = $workStartLocal->copy();

        while ($cursorLocal->lt($workEndLocal)) {

            $candidateEndLocal = $cursorLocal->copy()->addMinutes($blocks * 30);

            if ($candidateEndLocal->gt($workEndLocal)) {
                break;
            }

            try {
                $this->assertProviderAvailable(
                    $providerId,
                    $cursorLocal->copy()->utc(),
                    $blocks
                );

                $results[] = [
                    'start_at' => $cursorLocal->copy()->toIso8601String(),
                    'end_at' => $candidateEndLocal->copy()->toIso8601String(),
                    'blocks' => $blocks,
                ];

            } catch (ValidationException $e) {
                // skip
            }

            $cursorLocal = $cursorLocal->copy()->addMinutes(30);
        }

        return $results;
    }

    protected function assertNotInProviderTimeOff(int $providerId, Carbon $slotStartUtc, Carbon $slotEndUtc): void
    {
        $exists = ProviderTimeOff::query()
            ->where('provider_id', $providerId)
            ->where(function ($q) use ($slotStartUtc, $slotEndUtc) {
                $q->whereBetween('start_at', [$slotStartUtc, $slotEndUtc->copy()->subSecond()])
                    ->orWhereBetween('end_at', [$slotStartUtc->copy()->addSecond(), $slotEndUtc])
                    ->orWhere(function ($qq) use ($slotStartUtc, $slotEndUtc) {
                        $qq->where('start_at', '<=', $slotStartUtc)
                            ->where('end_at', '>=', $slotEndUtc);
                    });
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'availability' => 'Seçilen saat kişi için kapalı.',
            ]);
        }
    }

    protected function assertNotInGlobalBlackout(Carbon $slotStartUtc, Carbon $slotEndUtc): void
    {
        $exists = GlobalBlackout::query()
            ->where(function ($q) use ($slotStartUtc, $slotEndUtc) {
                $q->whereBetween('start_at', [$slotStartUtc, $slotEndUtc->copy()->subSecond()])
                    ->orWhereBetween('end_at', [$slotStartUtc->copy()->addSecond(), $slotEndUtc])
                    ->orWhere(function ($qq) use ($slotStartUtc, $slotEndUtc) {
                        $qq->where('start_at', '<=', $slotStartUtc)
                            ->where('end_at', '>=', $slotEndUtc);
                    });
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'availability' => 'Seçilen saat global kapalı zaman aralığında.',
            ]);
        }
    }

    protected function assertSlotNotOccupied(int $providerId, Carbon $slotStartUtc, ?int $ignoreAppointmentId = null): void
    {
        $query = AppointmentSlot::query()
            ->where('provider_id', $providerId)
            ->where('slot_start_at', $slotStartUtc);

        if ($ignoreAppointmentId) {
            $query->where('appointment_id', '!=', $ignoreAppointmentId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slot' => 'Seçilen saat aralığı bu kişi için dolu.',
            ]);
        }
    }

    protected function isSlotAligned(Carbon $dateLocal): bool
    {
        return ((int) $dateLocal->minute % 30 === 0) && ((int) $dateLocal->second === 0);
    }
}
