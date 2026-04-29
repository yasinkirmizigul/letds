<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('member.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Üye Şifre Yenileme Bağlantısı')
            ->greeting('Merhaba,')
            ->line('Üyelik hesabınız için bir şifre yenileme talebi aldık.')
            ->action('Şifremi Yenile', $url)
            ->line('Bu bağlantı 60 dakika boyunca geçerlidir.')
            ->line('Bu isteği siz yapmadıysanız bu e-postayı yok sayabilirsiniz.');
    }
}
