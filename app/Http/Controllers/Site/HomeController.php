<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\SiteFaq;
use App\Models\Site\SitePage;
use App\Services\Site\HomepageConfigurationService;
use App\Support\Site\SiteLocalization;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(HomepageConfigurationService $configurationService): View
    {
        $locale = SiteLocalization::currentLocale();
        $homepage = $configurationService->resolved($locale);
        $contactUrl = route('site.contact-messages.create', ['site_locale' => $locale]);

        foreach ($homepage['modes'] as $key => $mode) {
            $homepage['modes'][$key]['cta_url'] = $configurationService->safeLink(
                $mode['cta_url'] ?? null,
                $contactUrl
            );
        }

        return view('site.home', [
            'homepage' => $homepage,
            'homepageFaqs' => SiteFaq::query()
                ->with('translations')
                ->active()
                ->whereNull('site_page_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(5)
                ->get(),
            'aboutPage' => SitePage::query()
                ->with('translations')
                ->publishedVisible()
                ->where('slug', 'hakkimizda')
                ->first(),
            'faqUrl' => SiteLocalization::localizedRoute('site.faqs.index', locale: $locale),
        ]);
    }
}
