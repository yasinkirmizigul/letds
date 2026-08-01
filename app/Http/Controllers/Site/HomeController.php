<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\HomepageConfigurationService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(HomepageConfigurationService $configurationService): View
    {
        $locale = app()->getLocale();
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
        ]);
    }
}
