<?php

namespace App\Jobs;

use App\Mail\AppointmentUpdatedMail;
use App\Models\Appointment\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAppointmentUpdatedMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $appointmentId,
        public string $type
    ) {}

    public function handle(): void
    {
        $appointment = Appointment::query()
            ->with(['member', 'provider'])
            ->find($this->appointmentId);

        if (!$appointment) {
            return;
        }

        if (!$appointment->member?->email) {
            return;
        }

        Mail::to($appointment->member->email)
            ->send(new AppointmentUpdatedMail($appointment, $this->type));
    }
}
