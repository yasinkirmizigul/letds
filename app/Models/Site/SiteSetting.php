<?php

namespace App\Models\Site;

use App\Models\Concerns\HasSiteLocaleTranslations;
use App\Support\Site\SiteLocalization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteSetting extends Model
{
    use HasSiteLocaleTranslations;

    protected $fillable = [
        'site_name',
        'site_tagline',
        'hero_notice',
        'contact_email',
        'contact_phone',
        'whatsapp_phone',
        'address_line',
        'map_embed_url',
        'map_title',
        'office_hours',
        'footer_note',
        'member_terms_version',
        'member_terms_title',
        'member_terms_summary',
        'member_terms_content',
        'under_construction_enabled',
        'under_construction_title',
        'under_construction_message',
        'social_links',
        'ui_lines',
        'seo_base_url',
        'sitemap_include_home',
        'sitemap_include_pages',
        'sitemap_include_contact',
        'sitemap_include_member_pages',
        'sitemap_extra_urls',
        'sitemap_xml_content',
        'robots_txt_content',
        'llms_txt_content',
        'seo_files_generated_at',
        'mail_notifications_enabled',
        'notify_contact_messages',
        'notify_appointments',
        'mail_from_address',
        'mail_from_name',
        'smtp_host',
        'smtp_port',
        'smtp_scheme',
        'smtp_username',
        'smtp_password',
        'smtp_timeout',
    ];

    protected $casts = [
        'under_construction_enabled' => 'boolean',
        'social_links' => 'array',
        'ui_lines' => 'array',
        'sitemap_include_home' => 'boolean',
        'sitemap_include_pages' => 'boolean',
        'sitemap_include_contact' => 'boolean',
        'sitemap_include_member_pages' => 'boolean',
        'seo_files_generated_at' => 'datetime',
        'mail_notifications_enabled' => 'boolean',
        'notify_contact_messages' => 'boolean',
        'notify_appointments' => 'boolean',
        'smtp_port' => 'integer',
        'smtp_password' => 'encrypted',
        'smtp_timeout' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'site_name' => config('app.name'),
            'site_tagline' => 'Dijital vitrin ve içerik yönetimi',
            'member_terms_version' => config('membership_terms.version'),
        ]);
    }

    public function memberTermsVersion(): string
    {
        $version = trim((string) $this->member_terms_version);

        return $version !== '' ? $version : (string) config('membership_terms.version');
    }

    public function social(string $key, ?string $fallback = null): ?string
    {
        $links = is_array($this->social_links) ? $this->social_links : [];
        $value = $links[$key] ?? null;

        return filled($value) ? (string) $value : $fallback;
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SiteSettingTranslation::class)->orderBy('locale');
    }

    public function localized(string $field, ?string $locale = null, mixed $fallback = null): mixed
    {
        return $this->localizedValue($field, $locale, $fallback);
    }

    public function uiLine(string $key, ?string $locale = null): string
    {
        $locale = $locale ?: SiteLocalization::currentLocale();
        $fallbacks = config('site_ui_labels', []);
        $default = (string) ($fallbacks[$key]['default'] ?? $key);

        if (!SiteLocalization::isDefault($locale)) {
            $translation = $this->translationFor($locale);
            $translated = is_array($translation?->ui_lines) ? ($translation->ui_lines[$key] ?? null) : null;

            if (filled($translated)) {
                return (string) $translated;
            }
        }

        $base = is_array($this->ui_lines) ? ($this->ui_lines[$key] ?? null) : null;

        return filled($base) ? (string) $base : $default;
    }
}
