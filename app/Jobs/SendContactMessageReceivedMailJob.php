<?php

namespace App\Jobs;

use App\Mail\AdminContactMessageReceivedMail;
use App\Models\ContactMessage;
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

class SendContactMessageReceivedMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $contactMessageId
    ) {}

    public function handle(SiteMailConfigurator $mailConfigurator): void
    {
        $contactMessage = ContactMessage::query()
            ->with(['recipient:id,name,email', 'member:id,name,surname,email,phone'])
            ->find($this->contactMessageId);

        if (!$contactMessage?->recipient?->email) {
            return;
        }

        $settings = SiteSetting::current();

        if (!$mailConfigurator->readyFor(SiteMailConfigurator::FEATURE_CONTACT_MESSAGES, $settings)) {
            return;
        }

        try {
            Mail::to($contactMessage->recipient->email, $contactMessage->recipient->name)
                ->send(new AdminContactMessageReceivedMail($contactMessage, $settings));
        } catch (Throwable $e) {
            Log::error('Panel mesaj bildirimi gönderilemedi.', [
                'contact_message_id' => $contactMessage->id,
                'recipient_user_id' => $contactMessage->recipient_user_id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
