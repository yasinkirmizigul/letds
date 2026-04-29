<?php

namespace App\Mail;

use App\Models\Appointment\Appointment;
use App\Models\Site\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminAppointmentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $type,
        public SiteSetting $settings
    ) {}

    public function build()
    {
        $siteName = $this->settings->site_name ?: config('app.name');

        return $this
            ->subject($siteName . ' | ' . $this->subjectLine())
            ->view('emails.admin.appointment-notification', [
                'calendarUrl' => route('admin.appointments.calendar'),
                'title' => $this->title(),
                'intro' => $this->intro(),
                'badge' => $this->badge(),
            ]);
    }

    protected function subjectLine(): string
    {
        return match ($this->type) {
            'created' => 'Yeni randevu oluşturuldu',
            'transferred' => 'Randevu size aktarıldı',
            'resized' => 'Randevu süresi güncellendi',
            'cancelled_by_provider' => 'Randevu iptal edildi',
            'cancelled_by_member' => 'Üye randevuyu iptal etti',
            'rescheduled_by_member' => 'Üye randevuyu yeniden planladı',
            default => 'Randevu bildirimi',
        };
    }

    protected function title(): string
    {
        return match ($this->type) {
            'created' => 'Yeni randevu geldi',
            'transferred' => 'Randevu size aktarıldı',
            'resized' => 'Randevu süresi değişti',
            'cancelled_by_provider' => 'Randevu iptal edildi',
            'cancelled_by_member' => 'Üye randevuyu iptal etti',
            'rescheduled_by_member' => 'Üye randevuyu yeniden planladı',
            default => 'Randevu bildirimi',
        };
    }

    protected function intro(): string
    {
        return match ($this->type) {
            'created' => 'Takviminize yeni bir randevu eklendi. Detayları aşağıda bulabilirsiniz.',
            'transferred' => 'Bir randevu takviminize aktarıldı. Yeni plan bilgileri aşağıda yer alıyor.',
            'resized' => 'Takviminizdeki bir randevunun süresi güncellendi.',
            'cancelled_by_provider' => 'Takviminizdeki bir randevu panelden iptal edildi.',
            'cancelled_by_member' => 'Üye, takviminizdeki randevuyu iptal etti.',
            'rescheduled_by_member' => 'Üye, randevusunu yeni bir tarih ve saate taşıdı.',
            default => 'Randevuyla ilgili yeni bir bilgilendirme var.',
        };
    }

    protected function badge(): string
    {
        return match ($this->type) {
            'created' => 'Yeni randevu',
            'transferred' => 'Aktarım',
            'resized' => 'Güncelleme',
            'cancelled_by_provider', 'cancelled_by_member' => 'İptal',
            'rescheduled_by_member' => 'Yeniden planlandı',
            default => 'Bilgilendirme',
        };
    }
}
