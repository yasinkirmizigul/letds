<?php

namespace App\Http\Controllers\Admin\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Models\Appointment\GlobalBlackout;
use App\Models\Appointment\ProviderTimeOff;
use App\Models\Appointment\ProviderWorkingHour;
use App\Services\Appointment\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentSettingsController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {
    }

    public function index()
    {
        $providers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['provider', 'admin', 'superadmin']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'title']);

        return view('admin.pages.appointments.settings', [
            'pageTitle' => 'Randevu Ayarları',
            'providers' => $providers,
            'providerCount' => $providers->count(),
            'timeOffCount' => ProviderTimeOff::query()->count(),
            'blackoutCount' => GlobalBlackout::query()->count(),
        ]);
    }

    public function providerSchedule(User $provider)
    {
        $hours = ProviderWorkingHour::query()
            ->where('provider_id', $provider->id)
            ->orderBy('day_of_week')
            ->get();

        $timeOffs = ProviderTimeOff::query()
            ->where('provider_id', $provider->id)
            ->orderBy('start_at')
            ->get();

        return response()->json([
            'provider' => [
                'id' => $provider->id,
                'name' => $provider->name,
                'title' => $provider->title,
            ],
            'hours' => $hours,
            'time_offs' => $timeOffs,
            'summary' => $this->buildScheduleSummary($hours, $timeOffs),
        ]);
    }

    public function saveProviderSchedule(Request $request, User $provider)
    {
        $data = $request->validate([
            'days' => ['required', 'array', 'size:7'],
            'days.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'days.*.is_enabled' => ['required', 'boolean'],
            'days.*.start_time' => ['nullable', 'date_format:H:i'],
            'days.*.end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $this->validateScheduleDays($data['days']);

        DB::transaction(function () use ($provider, $data) {
            foreach ($data['days'] as $row) {
                ProviderWorkingHour::query()->updateOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'day_of_week' => $row['day_of_week'],
                    ],
                    [
                        'is_enabled' => (bool) $row['is_enabled'],
                        'start_time' => $row['is_enabled'] ? $row['start_time'] : null,
                        'end_time' => $row['is_enabled'] ? $row['end_time'] : null,
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Çalışma saatleri kaydedildi.',
        ]);
    }

    public function storeTimeOff(Request $request, User $provider)
    {
        $data = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'reason' => ['nullable', 'string', 'max:255'],
            'block_type' => ['nullable', 'string', 'in:manual,break,meeting,off'],
        ]);

        $timeOff = ProviderTimeOff::query()->create([
            'provider_id' => $provider->id,
            'start_at' => $this->parseLocalDateTime($data['start_at']),
            'end_at' => $this->parseLocalDateTime($data['end_at']),
            'reason' => $data['reason'] ?? null,
            'block_type' => $data['block_type'] ?? 'manual',
        ]);

        return response()->json([
            'message' => 'Kişisel kapalı zaman eklendi.',
            'data' => $timeOff,
        ], 201);
    }

    public function updateTimeOff(Request $request, User $provider, ProviderTimeOff $timeOff)
    {
        abort_unless((int) $timeOff->provider_id === (int) $provider->id, 404);

        $data = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'reason' => ['nullable', 'string', 'max:255'],
            'block_type' => ['nullable', 'string', 'in:manual,break,meeting,off'],
        ]);

        $timeOff->update([
            'start_at' => $this->parseLocalDateTime($data['start_at']),
            'end_at' => $this->parseLocalDateTime($data['end_at']),
            'reason' => $data['reason'] ?? null,
            'block_type' => $data['block_type'] ?? ($timeOff->block_type ?: 'manual'),
        ]);

        return response()->json([
            'message' => 'Kişisel kapalı zaman güncellendi.',
            'data' => $timeOff->fresh(),
        ]);
    }

    public function destroyTimeOff(User $provider, ProviderTimeOff $timeOff)
    {
        abort_unless((int) $timeOff->provider_id === (int) $provider->id, 404);

        $timeOff->delete();

        return response()->json([
            'message' => 'Kişisel kapalı zaman silindi.',
        ]);
    }

    public function listBlackouts()
    {
        return response()->json(
            GlobalBlackout::query()->orderBy('start_at')->get()
        );
    }

    public function storeBlackout(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $blackout = GlobalBlackout::query()->create([
            'label' => $data['label'],
            'start_at' => $this->parseLocalDateTime($data['start_at']),
            'end_at' => $this->parseLocalDateTime($data['end_at']),
        ]);

        return response()->json([
            'message' => 'Global kapalı zaman eklendi.',
            'data' => $blackout,
        ], 201);
    }

    public function updateBlackout(Request $request, GlobalBlackout $blackout)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $blackout->update([
            'label' => $data['label'],
            'start_at' => $this->parseLocalDateTime($data['start_at']),
            'end_at' => $this->parseLocalDateTime($data['end_at']),
        ]);

        return response()->json([
            'message' => 'Global kapalı zaman güncellendi.',
            'data' => $blackout->fresh(),
        ]);
    }

    public function destroyBlackout(GlobalBlackout $blackout)
    {
        $blackout->delete();

        return response()->json([
            'message' => 'Global kapalı zaman silindi.',
        ]);
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
            'blocks' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        $slots = $this->availabilityService->getAvailableStartsForDate(
            (int) $data['provider_id'],
            Carbon::parse($data['date']),
            (int) ($data['blocks'] ?? 1)
        );

        return response()->json($slots);
    }

    protected function parseLocalDateTime(string $value): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\TH:i', $value, 'Europe/Istanbul');
    }

    protected function validateScheduleDays(array $days): void
    {
        $errors = [];

        foreach ($days as $index => $row) {
            if (!(bool) ($row['is_enabled'] ?? false)) {
                continue;
            }

            $start = $row['start_time'] ?? null;
            $end = $row['end_time'] ?? null;

            if (!$start || !$end) {
                $errors["days.$index.start_time"] = 'Açık günler için başlangıç ve bitiş saati zorunludur.';
                continue;
            }

            if ($start >= $end) {
                $errors["days.$index.end_time"] = 'Bitiş saati başlangıç saatinden sonra olmalidir.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function buildScheduleSummary(Collection $hours, Collection $timeOffs): array
    {
        $enabledHours = $hours->filter(fn (ProviderWorkingHour $hour) => (bool) $hour->is_enabled);

        $weeklyMinutes = $enabledHours->sum(function (ProviderWorkingHour $hour) {
            if (!$hour->start_time || !$hour->end_time) {
                return 0;
            }

            return $this->minutesBetween((string) $hour->start_time, (string) $hour->end_time);
        });

        $nextTimeOff = $timeOffs
            ->first(fn (ProviderTimeOff $timeOff) => $timeOff->end_at && $timeOff->end_at->isFuture());

        return [
            'enabled_days' => $enabledHours->count(),
            'weekly_minutes' => $weeklyMinutes,
            'time_off_count' => $timeOffs->count(),
            'next_time_off_start_at' => $nextTimeOff?->start_at?->toIso8601String(),
            'next_time_off_end_at' => $nextTimeOff?->end_at?->toIso8601String(),
        ];
    }

    protected function minutesBetween(string $startTime, string $endTime): int
    {
        $start = Carbon::createFromFormat('H:i:s', strlen($startTime) === 5 ? $startTime . ':00' : $startTime);
        $end = Carbon::createFromFormat('H:i:s', strlen($endTime) === 5 ? $endTime . ':00' : $endTime);

        return max(0, $start->diffInMinutes($end, false));
    }
}
