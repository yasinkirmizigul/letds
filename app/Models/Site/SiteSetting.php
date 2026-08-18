<?php

namespace App\Models\Site;

use App\Models\Admin\Media\Media;
use App\Models\Concerns\HasSiteLocaleTranslations;
use App\Support\Site\SiteLocalization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteSetting extends Model
{
    use HasSiteLocaleTranslations;

    public const PALETTE_OPTIONS = [
        'coral' => [
            'label' => 'Probablue Mercan',
            'description' => 'Mevcut sıcak mercan ve petrol tonları.',
            'swatches' => ['#ec6367', '#445963', '#f6f3ed'],
        ],
        'probablue' => [
            'label' => 'Probablue Mavi',
            'description' => 'Referanstaki bilimsel, güven veren mavi tonlar.',
            'swatches' => ['#087cf0', '#102d5a', '#eef6ff'],
        ],
    ];

    private const PALETTE_TOKENS = [
        'coral' => [
            'primary' => '#ec6367', 'primary_active' => '#d84d54', 'primary_light' => '#fff0ef', 'primary_rgb' => '236, 99, 103',
            'ink' => '#445963', 'paper' => '#f6f3ed', 'background' => '#fffdfa', 'foreground' => '#263a43',
            'border' => '#dce4e2', 'muted' => '#f0f3f1', 'muted_foreground' => '#687b82', 'accent' => '#e8efee', 'accent_foreground' => '#314a54',
            'dark_paper' => '#172329', 'dark_background' => '#1b292f', 'dark_foreground' => '#eef4f2', 'dark_border' => '#35474e',
            'dark_muted' => '#23343b', 'dark_muted_foreground' => '#a7b8bc', 'dark_accent' => '#293c43', 'dark_accent_foreground' => '#f5f8f7', 'dark_primary_light' => '#422b30',
        ],
        'probablue' => [
            'primary' => '#087cf0', 'primary_active' => '#0567c9', 'primary_light' => '#e8f3ff', 'primary_rgb' => '8, 124, 240',
            'ink' => '#102d5a', 'paper' => '#eef6ff', 'background' => '#fbfdff', 'foreground' => '#142e50',
            'border' => '#d4e3f3', 'muted' => '#edf4fb', 'muted_foreground' => '#60758e', 'accent' => '#e4f0fd', 'accent_foreground' => '#173d6d',
            'dark_paper' => '#08182c', 'dark_background' => '#0d2038', 'dark_foreground' => '#edf6ff', 'dark_border' => '#294562',
            'dark_muted' => '#132b46', 'dark_muted_foreground' => '#9db2c8', 'dark_accent' => '#173653', 'dark_accent_foreground' => '#f3f8ff', 'dark_primary_light' => '#123c69',
        ],
    ];

    protected $fillable = [
        'site_name',
        'site_tagline',
        'site_palette',
        'admin_login_logo_media_id',
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

    public function palette(): string
    {
        $palette = (string) $this->site_palette;

        return array_key_exists($palette, self::PALETTE_OPTIONS) ? $palette : 'coral';
    }

    public function paletteCssVariables(): string
    {
        $tokens = self::PALETTE_TOKENS[$this->palette()];

        return collect($tokens)
            ->map(fn (string $value, string $key) => '--site-palette-'.str_replace('_', '-', $key).':'.$value)
            ->implode(';');
    }

    /**
     * The custom homepage has its own CSS contract, so the global blue palette
     * needs an explicit bridge. Coral keeps the manually managed home colors.
     *
     * @return array<string, string>
     */
    public function homepagePaletteStyles(): array
    {
        if ($this->palette() !== 'probablue') {
            return [];
        }

        $tokens = self::PALETTE_TOKENS['probablue'];

        return [
            '--home-before-bg' => $tokens['paper'],
            '--home-after-bg' => $tokens['primary'],
            '--home-before-pattern-color' => $tokens['ink'],
            '--home-after-pattern-color' => '#ffffff',
            '--home-before-text' => $tokens['ink'],
            '--home-after-text' => '#ffffff',
            '--home-hero-before-text' => $tokens['ink'],
            '--home-hero-after-text' => '#ffffff',
            '--home-before-highlight' => $tokens['primary'],
            '--home-after-highlight' => '#b9dcff',
            '--home-before-hotspot' => $tokens['primary'],
            '--home-after-hotspot' => '#ffffff',
            '--home-drag-handle' => $tokens['primary_active'],
            '--home-stat-before' => $tokens['primary'],
            '--home-stat-after' => '#ffffff',
            '--home-cta-before-text' => $tokens['primary_active'],
            '--home-cta-after-text' => '#ffffff',
            '--home-cta-before-hover-bg' => $tokens['primary'],
            '--home-cta-before-hover-text' => '#ffffff',
            '--home-cta-after-hover-bg' => '#ffffff',
            '--home-cta-after-hover-text' => $tokens['primary_active'],
            '--home-logo' => '#ffffff',
            '--home-sticky-header-bg' => $tokens['paper'],
            '--home-sticky-logo' => $tokens['primary'],
            '--home-feature-palette-accent' => $tokens['primary'],
            '--home-feature-palette-bg' => $tokens['paper'],
            '--home-feature-palette-card' => $tokens['background'],
            '--home-feature-palette-text' => $tokens['ink'],
            '--home-feature-palette-muted' => $tokens['muted_foreground'],
            '--home-feature-palette-line' => $tokens['border'],
            '--home-feature-palette-light-bg' => $tokens['background'],
            '--home-feature-palette-light-card' => $tokens['muted'],
            '--home-feature-palette-dark-bg' => $tokens['dark_background'],
            '--home-feature-palette-dark-card' => $tokens['dark_muted'],
            '--home-feature-palette-dark-text' => $tokens['dark_foreground'],
            '--home-feature-palette-dark-muted' => $tokens['dark_muted_foreground'],
            '--home-feature-palette-dark-line' => $tokens['dark_border'],
        ];
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

    public function adminLoginLogo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'admin_login_logo_media_id');
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

        if (! SiteLocalization::isDefault($locale)) {
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
