<?php

namespace App\Mail;

use App\Models\Site\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SiteMailTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SiteSetting $settings
    ) {}

    public function build()
    {
        $siteName = $this->settings->site_name ?: config('app.name');

        return $this
            ->subject($siteName . ' | SMTP test e-postası')
            ->view('emails.admin.test');
    }
}
