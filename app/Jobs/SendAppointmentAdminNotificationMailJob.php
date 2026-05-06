<?php

namespace App\Jobs;

use App\Mail\AdminAppointmentNotificationMail;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\Site\SiteSetting;
use App\Services\Mail\SiteMailConfigurator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAppointmentAdminNotificationMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $appointmentId,
        public string $type,
        public ?int $actorUserId = null
    ) {}

    public function handle(SiteMailConfigurator $mailConfigurator): void
    {
        $appointment = Appointment::query()
            ->with(['member:id,name,surname,email,phone', 'provider:id,name,email,title', 'parent:id,start_at,end_at,status'])
            ->find($this->appointmentId);

        if (!$appointment) {
            return;
        }

        $settings = SiteSetting::current();

        if (!$mailConfigurator->readyFor(SiteMailConfigurator::FEATURE_APPOINTMENTS, $settings)) {
            return;
        }

        $recipients = collect();

        if ($appointment->provider?->email) {
            $recipients->push($appointment->provider);
        }

        User::query()
            ->adminAccessible()
            ->with('roles.permissions')
            ->get()
            ->filter(fn (User $user) => $user->canAccess('appointments.view') && filled($user->email))
            ->each(fn (User $user) => $recipients->push($user));

        $recipients
            ->unique('id')
            ->reject(fn (User $recipient) => $this->actorUserId && (int) $recipient->id === (int) $this->actorUserId)
            ->each(function (User $recipient) use ($appointment, $settings) {
                try {
                    Mail::to($recipient->email, $recipient->name)
                        ->send(new AdminAppointmentNotificationMail($appointment, $this->type, $settings));
                } catch (Throwable $e) {
                    Log::error('Panel randevu bildirimi gönderilemedi.', [
                        'appointment_id' => $appointment->id,
                        'recipient_user_id' => $recipient->id,
                        'type' => $this->type,
                        'message' => $e->getMessage(),
                    ]);
                }
            });
    }
}
