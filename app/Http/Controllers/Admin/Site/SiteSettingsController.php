<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\HomeSlider;
use App\Models\Site\SiteCounter;
use App\Models\Site\SiteFaq;
use App\Models\Site\SiteNavigationItem;
use App\Models\Site\SitePage;
use App\Models\Site\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.pages.site.settings.edit', [
            'settings' => SiteSetting::current(),
            'stats' => [
                'pages' => SitePage::query()->count(),
                'sliders' => HomeSlider::query()->count(),
                'faqs' => SiteFaq::query()->count(),
                'navigation' => SiteNavigationItem::query()->count(),
                'counters' => SiteCounter::query()->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'hero_notice' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:80'],
            'whatsapp_phone' => ['nullable', 'string', 'max:80'],
            'address_line' => ['nullable', 'string'],
            'map_embed_url' => ['nullable', 'string', 'max:2000'],
            'map_title' => ['nullable', 'string', 'max:255'],
            'office_hours' => ['nullable', 'string'],
            'footer_note' => ['nullable', 'string'],
            'under_construction_enabled' => ['nullable', 'boolean'],
            'under_construction_title' => ['nullable', 'string', 'max:255'],
            'under_construction_message' => ['nullable', 'string'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', 'max:255'],
        ]);

        SiteSetting::current()->update([
            'site_name' => $validated['site_name'] ?? null,
            'site_tagline' => $validated['site_tagline'] ?? null,
            'hero_notice' => $validated['hero_notice'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'whatsapp_phone' => $validated['whatsapp_phone'] ?? null,
            'address_line' => $validated['address_line'] ?? null,
            'map_embed_url' => $validated['map_embed_url'] ?? null,
            'map_title' => $validated['map_title'] ?? null,
            'office_hours' => $validated['office_hours'] ?? null,
            'footer_note' => $validated['footer_note'] ?? null,
            'under_construction_enabled' => $request->boolean('under_construction_enabled'),
            'under_construction_title' => $validated['under_construction_title'] ?? null,
            'under_construction_message' => $validated['under_construction_message'] ?? null,
            'social_links' => array_filter($validated['social_links'] ?? [], fn ($value) => filled($value)),
        ]);

        return redirect()
            ->route('admin.site.settings.edit')
            ->with('success', 'Site ayarları güncellendi.');
    }
}
