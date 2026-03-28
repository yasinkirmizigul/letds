<?php

namespace App\Mail;

use App\Models\Appointment\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $type // transferred | resized | cancelled
    ) {}

    public function build()
    {
        $subject = match ($this->type) {
            'transferred' => 'Randevunuzun Saati Güncellendi',
            'resized' => 'Randevunuzun Süresi Güncellendi',
            'cancelled' => 'Randevunuz İptal Edildi',
            default => 'Randevu Bilgilendirmesi',
        };

        return $this
            ->subject($subject)
            ->view('emails.appointments.updated');
    }
}
