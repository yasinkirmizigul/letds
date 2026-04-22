<?php

namespace App\Services\Appointment;

use App\Models\Appointment\AppointmentSlot;
use App\Models\Appointment\GlobalBlackout;
use App\Models\Appointment\ProviderTimeOff;
use App\Models\Appointment\ProviderWorkingHour;
use App\Support\DateTimeHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    protected string $tz = DateTimeHelper::APP_TZ;

    public function assertProviderAvailable(
        int $providerId,
        Carbon $startAt,
        int $blocks,
        ?int $ignoreAppointmentId = null
    ): void {
        $startAt = $startAt->copy()->setTimezone($this->tz)->seconds(0);
        $blocks = max(1, $blocks);

        if (!$this->isSlotAligned($startAt)) {
            throw ValidationException::withMessages([
                'start_at' => 'Başlangıç saati 30 dakikalık aralığa uygun olmalı.',
            ]);
        }

        $workingHour = $this->findWorkingHourForDay(
            $providerId,
            $this->resolveStoredDayOfWeek($startAt)
        );

        if (!$workingHour || !$workingHour->start_time || !$workingHour->end_time) {
            throw ValidationException::withMessages([
                'availability' => 'Seçilen gün bu kişi için kapalı.',
            ]);
        }

        $dayStart = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $startAt->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->start_time),
            $this->tz
        );

        $dayEnd = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $startAt->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->end_time),
            $this->tz
        );

        for ($i = 0; $i < $blocks; $i++) {
            $slotStart = $startAt->copy()->addMinutes($i * 30);
            $slotEnd = $slotStart->copy()->addMinutes(30);

            if ($slotStart->lt($dayStart) || $slotEnd->gt($dayEnd)) {
                throw ValidationException::withMessages([
                    'availability' => 'Seçilen saat çalışma saatleri dışında.',
                ]);
            }

            $this->assertNotInProviderTimeOff($providerId, $slotStart, $slotEnd);
            $this->assertNotInGlobalBlackout($slotStart, $slotEnd);
            $this->assertSlotNotOccupied($providerId, $slotStart, $ignoreAppointmentId);
        }
    }

    public function getAvailableStartsForDate(int $providerId, Carbon $date, int $blocks = 1): array
    {
        $blocks = max(1, $blocks);

        $dateLocal = $date->copy()->setTimezone($this->tz)->startOfDay();
        $dayOfWeek = $this->resolveStoredDayOfWeek($dateLocal);

        $workingHour = $this->findWorkingHourForDay($providerId, $dayOfWeek);

        if (!$workingHour || !$workingHour->start_time || !$workingHour->end_time) {
            return [];
        }

        $workStart = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $dateLocal->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->start_time),
            $this->tz
        );

        $workEnd = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $dateLocal->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->end_time),
            $this->tz
        );

        $results = [];
        $cursor = $workStart->copy();

        while ($cursor->lt($workEnd)) {
            $candidateEnd = $cursor->copy()->addMinutes($blocks * 30);

            if ($candidateEnd->gt($workEnd)) {
                break;
            }

            try {
                $this->assertProviderAvailable(
                    $providerId,
                    $cursor->copy(),
                    $blocks
                );

                $results[] = [
                    'start_at' => $cursor->copy()->toIso8601String(),
                    'end_at' => $candidateEnd->copy()->toIso8601String(),
                    'blocks' => $blocks,
                ];
            } catch (ValidationException $e) {
                // skip invalid slot
            }

            $cursor = $cursor->copy()->addMinutes(30);
        }

        return $results;
    }

    protected function assertNotInProviderTimeOff(int $providerId, Carbon $slotStart, Carbon $slotEnd): void
    {
        $exists = ProviderTimeOff::query()
            ->where('provider_id', $providerId)
            ->where(function ($q) use ($slotStart, $slotEnd) {
                $q->whereBetween('start_at', [$slotStart, $slotEnd->copy()->subSecond()])
                    ->orWhereBetween('end_at', [$slotStart->copy()->addSecond(), $slotEnd])
                    ->orWhere(function ($qq) use ($slotStart, $slotEnd) {
                        $qq->where('start_at', '<=', $slotStart)
                            ->where('end_at', '>=', $slotEnd);
                    });
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'availability' => 'Seçilen saat kişi için kapalı.',
            ]);
        }
    }

    protected function assertNotInGlobalBlackout(Carbon $slotStart, Carbon $slotEnd): void
    {
        $exists = GlobalBlackout::query()
            ->where(function ($q) use ($slotStart, $slotEnd) {
                $q->whereBetween('start_at', [$slotStart, $slotEnd->copy()->subSecond()])
                    ->orWhereBetween('end_at', [$slotStart->copy()->addSecond(), $slotEnd])
                    ->orWhere(function ($qq) use ($slotStart, $slotEnd) {
                        $qq->where('start_at', '<=', $slotStart)
                            ->where('end_at', '>=', $slotEnd);
                    });
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'availability' => 'Seçilen saat global kapalı zaman aralığında.',
            ]);
        }
    }

    protected function assertSlotNotOccupied(int $providerId, Carbon $slotStart, ?int $ignoreAppointmentId = null): void
    {
        $query = AppointmentSlot::query()
            ->where('provider_id', $providerId)
            ->where('slot_start_at', $slotStart);

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

    protected function resolveStoredDayOfWeek(Carbon $dateLocal): int
    {
        return (int) $dateLocal->dayOfWeek;
    }

    protected function findWorkingHourForDay(int $providerId, int $dayOfWeek): ?ProviderWorkingHour
    {
        $acceptableDays = $dayOfWeek === 0 ? [0, 7] : [$dayOfWeek];

        return ProviderWorkingHour::query()
            ->where('provider_id', $providerId)
            ->whereIn('day_of_week', $acceptableDays)
            ->where('is_enabled', true)
            ->first();
    }

    public function getCalendarAvailability(int $providerId, Carbon $start, Carbon $end): array
    {
        $startLocal = $start->copy()->setTimezone($this->tz)->startOfDay();
        $endLocal = $end->copy()->setTimezone($this->tz)->endOfDay();

        $workingHours = ProviderWorkingHour::query()
            ->where('provider_id', $providerId)
            ->where('is_enabled', true)
            ->get()
            ->mapWithKeys(function (ProviderWorkingHour $workingHour) {
                $normalizedDay = (int) $workingHour->day_of_week;

                if ($normalizedDay === 7) {
                    $normalizedDay = 0;
                }

                return [$normalizedDay => $workingHour];
            });

        $occupied = AppointmentSlot::query()
            ->where('provider_id', $providerId)
            ->whereBetween('slot_start_at', [$startLocal, $endLocal])
            ->pluck('slot_start_at')
            ->map(fn ($d) => $d->copy()->setTimezone($this->tz)->format('Y-m-d H:i'))
            ->toArray();

        $occupiedMap = array_flip($occupied);

        $timeOffs = ProviderTimeOff::query()
            ->where('provider_id', $providerId)
            ->where(function ($q) use ($startLocal, $endLocal) {
                $q->whereBetween('start_at', [$startLocal, $endLocal])
                    ->orWhereBetween('end_at', [$startLocal, $endLocal])
                    ->orWhere(function ($qq) use ($startLocal, $endLocal) {
                        $qq->where('start_at', '<=', $startLocal)
                            ->where('end_at', '>=', $endLocal);
                    });
            })
            ->get(['start_at', 'end_at']);

        $blackouts = GlobalBlackout::query()
            ->where(function ($q) use ($startLocal, $endLocal) {
                $q->whereBetween('start_at', [$startLocal, $endLocal])
                    ->orWhereBetween('end_at', [$startLocal, $endLocal])
                    ->orWhere(function ($qq) use ($startLocal, $endLocal) {
                        $qq->where('start_at', '<=', $startLocal)
                            ->where('end_at', '>=', $endLocal);
                    });
            })
            ->get(['start_at', 'end_at']);

        $days = [];
        for ($d = $startLocal->copy(); $d->lte($endLocal); $d->addDay()) {
            $slots = $this->getAvailableStartsForDateFast(
                $d,
                $occupiedMap,
                $workingHours,
                $timeOffs,
                $blackouts
            );

            $days[$d->toDateString()] = [
                'has_availability' => count($slots) > 0,
                'free_count' => count($slots),
            ];
        }

        return $days;
    }

    protected function getAvailableStartsForDateFast(
        Carbon $date,
        array $occupiedMap,
        Collection $workingHours,
        Collection $timeOffs,
        Collection $blackouts
    ): array {
        $dateLocal = $date->copy()->setTimezone($this->tz)->startOfDay();
        $dayOfWeek = $this->resolveStoredDayOfWeek($dateLocal);

        $workingHour = $workingHours->get($dayOfWeek);

        if (!$workingHour || !$workingHour->start_time || !$workingHour->end_time) {
            return [];
        }

        $workStart = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $dateLocal->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->start_time),
            $this->tz
        );

        $workEnd = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $dateLocal->format('Y-m-d') . ' ' . DateTimeHelper::normalizeTimeString($workingHour->end_time),
            $this->tz
        );

        $results = [];
        $cursor = $workStart->copy();

        while ($cursor->lt($workEnd)) {
            $slotEnd = $cursor->copy()->addMinutes(30);

            if ($slotEnd->gt($workEnd)) {
                break;
            }

            $key = $cursor->format('Y-m-d H:i');

            if (isset($occupiedMap[$key])) {
                $cursor->addMinutes(30);
                continue;
            }

            $blockedByTimeOff = $timeOffs->contains(function ($item) use ($cursor, $slotEnd) {
                return $item->start_at < $slotEnd && $item->end_at > $cursor;
            });

            if ($blockedByTimeOff) {
                $cursor->addMinutes(30);
                continue;
            }

            $blockedByBlackout = $blackouts->contains(function ($item) use ($cursor, $slotEnd) {
                return $item->start_at < $slotEnd && $item->end_at > $cursor;
            });

            if ($blockedByBlackout) {
                $cursor->addMinutes(30);
                continue;
            }

            $results[] = $key;
            $cursor->addMinutes(30);
        }

        return $results;
    }
}
