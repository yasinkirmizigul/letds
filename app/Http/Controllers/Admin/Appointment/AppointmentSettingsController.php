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
use Illuminate\Support\Facades\DB;

class AppointmentSettingsController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    public function index()
    {
        $providers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('slug', ['provider', 'admin', 'superadmin']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'title']);

        return view('admin.pages.appointments.settings',[
            'pageTitle' => 'Randevu Ayarları',
        ], compact('providers'));
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
            'hours' => $hours,
            'time_offs' => $timeOffs,
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
        ]);

        $timeOff = ProviderTimeOff::query()->create([
            'provider_id' => $provider->id,
            'start_at' => \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['start_at'], 'Europe/Istanbul'),
            'end_at' => \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['end_at'], 'Europe/Istanbul'),
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'Kişisel kapalı zaman eklendi.',
            'data' => $timeOff,
        ], 201);
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
            'start_at' => \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['start_at'], 'Europe/Istanbul'),
            'end_at' => \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['end_at'], 'Europe/Istanbul'),
        ]);

        return response()->json([
            'message' => 'Global kapalı zaman eklendi.',
            'data' => $blackout,
        ], 201);
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
}
