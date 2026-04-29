<?php

namespace App\Services\Mail;

use App\Models\Site\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SiteMailConfigurator
{
    public const FEATURE_CONTACT_MESSAGES = 'contact_messages';
    public const FEATURE_APPOINTMENTS = 'appointments';

    public function apply(?SiteSetting $settings = null): bool
    {
        $settings ??= SiteSetting::current();

        if (!$this->isConfigured($settings)) {
            return false;
        }

        $scheme = $this->smtpScheme($settings);
        $disableAutoTls = $settings->smtp_scheme === 'smtp_plain';

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => trim((string) $settings->smtp_host),
            'mail.mailers.smtp.port' => (int) $settings->smtp_port,
            'mail.mailers.smtp.username' => filled($settings->smtp_username) ? (string) $settings->smtp_username : null,
            'mail.mailers.smtp.password' => filled($settings->smtp_password) ? (string) $settings->smtp_password : null,
            'mail.mailers.smtp.timeout' => $settings->smtp_timeout ?: null,
            'mail.mailers.smtp.auto_tls' => !$disableAutoTls,
            'mail.from.address' => $this->fromAddress($settings),
            'mail.from.name' => $this->fromName($settings),
        ]);

        Mail::purge('smtp');

        return true;
    }

    public function readyFor(string $feature, ?SiteSetting $settings = null): bool
    {
        $settings ??= SiteSetting::current();

        if (!$settings->mail_notifications_enabled) {
            return false;
        }

        if (!$this->featureEnabled($feature, $settings)) {
            return false;
        }

        if ($this->apply($settings)) {
            return true;
        }

        Log::warning('Panel e-posta bildirimi atlandı: SMTP ayarları eksik.', [
            'feature' => $feature,
            'site_setting_id' => $settings->id,
        ]);

        return false;
    }

    protected function isConfigured(SiteSetting $settings): bool
    {
        return filled($settings->smtp_host)
            && filled($settings->smtp_port)
            && filled($this->fromAddress($settings));
    }

    protected function featureEnabled(string $feature, SiteSetting $settings): bool
    {
        return match ($feature) {
            self::FEATURE_CONTACT_MESSAGES => (bool) $settings->notify_contact_messages,
            self::FEATURE_APPOINTMENTS => (bool) $settings->notify_appointments,
            default => true,
        };
    }

    protected function smtpScheme(SiteSetting $settings): string
    {
        $scheme = trim((string) $settings->smtp_scheme);

        if ($scheme === 'smtps') {
            return 'smtps';
        }

        return 'smtp';
    }

    protected function fromAddress(SiteSetting $settings): ?string
    {
        $address = $settings->mail_from_address ?: $settings->contact_email ?: config('mail.from.address');

        return filled($address) ? trim((string) $address) : null;
    }

    protected function fromName(SiteSetting $settings): string
    {
        return trim((string) ($settings->mail_from_name ?: $settings->site_name ?: config('mail.from.name') ?: config('app.name')));
    }
}
