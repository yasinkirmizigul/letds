<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
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
        $servicesUrl = SiteLocalization::localizedRoute('site.services.index', locale: $locale);

        foreach ($homepage['modes'] as $key => $mode) {
            $ctaUrl = $configurationService->safeLink(
                $mode['cta_url'] ?? null,
                $key === 'analysis' ? $servicesUrl : $contactUrl
            );

            $homepage['modes'][$key]['cta_url'] = str_starts_with($ctaUrl, '#')
                ? ($key === 'analysis' ? $servicesUrl : $contactUrl)
                : $ctaUrl;
        }

        return view('site.home', [
            'homepage' => $homepage,
        ]);
    }
}
