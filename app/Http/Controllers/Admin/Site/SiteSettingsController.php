<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Mail\SiteMailTestMail;
use App\Models\Site\HomeSlider;
use App\Models\Site\SiteCounter;
use App\Models\Site\SiteFaq;
use App\Models\Site\SiteNavigationItem;
use App\Models\Site\SitePage;
use App\Models\Site\SiteSetting;
use App\Services\Mail\SiteMailConfigurator;
use App\Services\Site\SeoFileGenerator;
use App\Services\Site\SiteTranslationSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SiteSettingsController extends Controller
{
    public function __construct(
        private readonly SiteTranslationSyncService $translationSyncService,
    ) {}

    public function edit(SeoFileGenerator $seoFileGenerator): View
    {
        return view('admin.pages.site.settings.edit', [
            'settings' => SiteSetting::current()->loadMissing('translations'),
            'seoFileStatus' => $seoFileGenerator->status(),
            'stats' => [
                'pages' => SitePage::query()->count(),
                'sliders' => HomeSlider::query()->count(),
                'faqs' => SiteFaq::query()->count(),
                'navigation' => SiteNavigationItem::query()->count(),
                'counters' => SiteCounter::query()->count(),
            ],
        ]);
    }

    public function update(Request $request, SeoFileGenerator $seoFileGenerator): RedirectResponse
    {
        $isSeoDraft = $request->input('submit_action') === 'generate_seo_draft';

        $validated = $request->validate([
            'submit_action' => ['nullable', 'string', 'in:save,generate_seo_draft,save_generate_seo'],
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
            'member_terms_version' => ['nullable', 'string', 'max:50'],
            'member_terms_title' => ['nullable', 'string', 'max:255'],
            'member_terms_summary' => ['nullable', 'string'],
            'member_terms_content' => ['nullable', 'string'],
            'under_construction_enabled' => ['nullable', 'boolean'],
            'under_construction_title' => ['nullable', 'string', 'max:255'],
            'under_construction_message' => ['nullable', 'string'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', 'max:255'],
            'ui_lines' => ['nullable', 'array'],
            'ui_lines.*' => ['nullable', 'string', 'max:500'],
            'seo_base_url' => ['nullable', 'string', 'max:255'],
            'sitemap_include_home' => ['nullable', 'boolean'],
            'sitemap_include_pages' => ['nullable', 'boolean'],
            'sitemap_include_contact' => ['nullable', 'boolean'],
            'sitemap_include_member_pages' => ['nullable', 'boolean'],
            'sitemap_extra_urls' => ['nullable', 'string'],
            'sitemap_xml_content' => ['nullable', 'string'],
            'robots_txt_content' => ['nullable', 'string'],
            'llms_txt_content' => ['nullable', 'string'],
            'mail_notifications_enabled' => ['nullable', 'boolean'],
            'notify_contact_messages' => ['nullable', 'boolean'],
            'notify_appointments' => ['nullable', 'boolean'],
            'mail_from_address' => $isSeoDraft ? ['nullable', 'email', 'max:255'] : ['nullable', 'required_if:mail_notifications_enabled,1', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'smtp_host' => $isSeoDraft ? ['nullable', 'string', 'max:255'] : ['nullable', 'required_if:mail_notifications_enabled,1', 'string', 'max:255'],
            'smtp_port' => $isSeoDraft ? ['nullable', 'integer', 'min:1', 'max:65535'] : ['nullable', 'required_if:mail_notifications_enabled,1', 'integer', 'min:1', 'max:65535'],
            'smtp_scheme' => ['nullable', 'string', 'in:smtp,smtps,smtp_plain'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:1000'],
            'smtp_password_clear' => ['nullable', 'boolean'],
            'smtp_timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
            'translations' => ['nullable', 'array'],
            'translations.*.site_name' => ['nullable', 'string', 'max:255'],
            'translations.*.site_tagline' => ['nullable', 'string', 'max:255'],
            'translations.*.hero_notice' => ['nullable', 'string', 'max:500'],
            'translations.*.address_line' => ['nullable', 'string'],
            'translations.*.map_title' => ['nullable', 'string', 'max:255'],
            'translations.*.office_hours' => ['nullable', 'string'],
            'translations.*.footer_note' => ['nullable', 'string'],
            'translations.*.member_terms_title' => ['nullable', 'string', 'max:255'],
            'translations.*.member_terms_summary' => ['nullable', 'string'],
            'translations.*.member_terms_content' => ['nullable', 'string'],
            'translations.*.under_construction_title' => ['nullable', 'string', 'max:255'],
            'translations.*.under_construction_message' => ['nullable', 'string'],
            'translations.*.ui_lines' => ['nullable', 'array'],
            'translations.*.ui_lines.*' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = SiteSetting::current();

        $settingsPayload = [
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
            'member_terms_version' => $validated['member_terms_version'] ?? config('membership_terms.version'),
            'member_terms_title' => $validated['member_terms_title'] ?? null,
            'member_terms_summary' => $validated['member_terms_summary'] ?? null,
            'member_terms_content' => $validated['member_terms_content'] ?? null,
            'under_construction_enabled' => $request->boolean('under_construction_enabled'),
            'under_construction_title' => $validated['under_construction_title'] ?? null,
            'under_construction_message' => $validated['under_construction_message'] ?? null,
            'social_links' => array_filter($validated['social_links'] ?? [], fn ($value) => filled($value)),
            'ui_lines' => array_filter($validated['ui_lines'] ?? [], fn ($value) => filled($value)),
            'seo_base_url' => $validated['seo_base_url'] ?? null,
            'sitemap_include_home' => $request->boolean('sitemap_include_home'),
            'sitemap_include_pages' => $request->boolean('sitemap_include_pages'),
            'sitemap_include_contact' => $request->boolean('sitemap_include_contact'),
            'sitemap_include_member_pages' => $request->boolean('sitemap_include_member_pages'),
            'sitemap_extra_urls' => $validated['sitemap_extra_urls'] ?? null,
            'sitemap_xml_content' => $validated['sitemap_xml_content'] ?? null,
            'robots_txt_content' => $validated['robots_txt_content'] ?? null,
            'llms_txt_content' => $validated['llms_txt_content'] ?? null,
            'mail_notifications_enabled' => $request->boolean('mail_notifications_enabled'),
            'notify_contact_messages' => $request->boolean('notify_contact_messages'),
            'notify_appointments' => $request->boolean('notify_appointments'),
            'mail_from_address' => $validated['mail_from_address'] ?? null,
            'mail_from_name' => $validated['mail_from_name'] ?? null,
            'smtp_host' => $validated['smtp_host'] ?? null,
            'smtp_port' => $validated['smtp_port'] ?? null,
            'smtp_scheme' => $validated['smtp_scheme'] ?? 'smtp',
            'smtp_username' => $validated['smtp_username'] ?? null,
            'smtp_timeout' => $validated['smtp_timeout'] ?? null,
        ];

        if ($isSeoDraft) {
            $previewSettings = $settings->replicate();
            $previewSettings->forceFill($settingsPayload);
            $contents = $seoFileGenerator->automaticContents($previewSettings);

            return redirect()
                ->route('admin.site.settings.edit')
                ->withInput(array_replace($request->except('smtp_password'), [
                    'sitemap_xml_content' => $contents['sitemap_xml_content'],
                    'robots_txt_content' => $contents['robots_txt_content'],
                    'llms_txt_content' => $contents['llms_txt_content'],
                ]))
                ->with('success', 'Otomatik SEO içerikleri hazırlandı. İçerikleri düzenleyip dosyaları oluşturabilirsiniz.');
        }

        $settings->update($settingsPayload);

        if ($request->boolean('smtp_password_clear')) {
            $settings->forceFill(['smtp_password' => null])->save();
        } elseif ($request->filled('smtp_password')) {
            $settings->forceFill(['smtp_password' => $validated['smtp_password']])->save();
        }

        $this->translationSyncService->sync(
            $settings,
            'translations',
            $validated['translations'] ?? [],
            [
                'site_name',
                'site_tagline',
                'hero_notice',
                'address_line',
                'map_title',
                'office_hours',
                'footer_note',
                'member_terms_title',
                'member_terms_summary',
                'member_terms_content',
                'under_construction_title',
                'under_construction_message',
                'ui_lines',
            ]
        );

        if (($validated['submit_action'] ?? 'save') === 'save_generate_seo') {
            try {
                $result = $seoFileGenerator->generate($settings->refresh());
            } catch (Throwable $e) {
                Log::error('SEO dosyaları oluşturulamadı.', [
                    'message' => $e->getMessage(),
                ]);

                return redirect()
                    ->route('admin.site.settings.edit')
                    ->with('error', 'Site ayarları kaydedildi ancak SEO dosyaları oluşturulamadı. Public klasör yazma iznini kontrol edin.');
            }

            return redirect()
                ->route('admin.site.settings.edit')
                ->with('success', 'Site ayarları güncellendi; sitemap.xml, robots.txt ve llms.txt oluşturuldu. Sitemap URL sayısı: ' . $result['entries']);
        }

        return redirect()
            ->route('admin.site.settings.edit')
            ->with('success', 'Site ayarları güncellendi.');
    }

    public function sendTestMail(Request $request, SiteMailConfigurator $mailConfigurator): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        $settings = SiteSetting::current();

        if (!$mailConfigurator->apply($settings)) {
            return back()
                ->withInput()
                ->with('error', 'Test e-postası için SMTP host, port ve gönderen adresini kaydedin.');
        }

        try {
            Mail::to($validated['test_email'])
                ->send(new SiteMailTestMail($settings));
        } catch (Throwable $e) {
            Log::error('SMTP test e-postası gönderilemedi.', [
                'recipient' => $validated['test_email'],
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Test e-postası gönderilemedi. SMTP bilgilerini ve sunucu erişimini kontrol edin.');
        }

        return back()->with('success', 'Test e-postası gönderildi.');
    }
}
