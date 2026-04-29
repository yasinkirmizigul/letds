<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\Site\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminContactMessageReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public SiteSetting $settings
    ) {}

    public function build()
    {
        $siteName = $this->settings->site_name ?: config('app.name');
        $subject = $this->contactMessage->subject ?: 'Yeni mesaj';

        $mail = $this
            ->subject($siteName . ' | Yeni mesaj: ' . $subject)
            ->view('emails.admin.contact-message-received', [
                'messageUrl' => route('admin.messages.show', $this->contactMessage),
            ]);

        if (filled($this->contactMessage->sender_email)) {
            $mail->replyTo(
                (string) $this->contactMessage->sender_email,
                $this->contactMessage->sender_full_name ?: null
            );
        }

        return $mail;
    }
}
