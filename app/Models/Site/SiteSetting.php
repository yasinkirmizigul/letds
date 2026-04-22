<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
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
        'under_construction_enabled',
        'under_construction_title',
        'under_construction_message',
        'social_links',
    ];

    protected $casts = [
        'under_construction_enabled' => 'boolean',
        'social_links' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'site_name' => config('app.name'),
            'site_tagline' => 'Dijital vitrin ve içerik yönetimi',
        ]);
    }

    public function social(string $key, ?string $fallback = null): ?string
    {
        $links = is_array($this->social_links) ? $this->social_links : [];
        $value = $links[$key] ?? null;

        return filled($value) ? (string) $value : $fallback;
    }
}
