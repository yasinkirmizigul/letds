<?php

namespace App\Http\Controllers\Site\Cms;

use App\Http\Controllers\Controller;
use App\Models\Site\HomeSlider;
use App\Models\Site\SiteCounter;
use App\Models\Site\SiteFaq;
use App\Models\Site\SitePage;
use App\Models\Site\SiteSetting;
use App\Support\Site\SiteLocalization;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('site.cms.home', [
            'pageTitle' => SiteSetting::current()->localized('site_name') ?: SiteLocalization::currentLanguage()->native_name,
            'sliders' => HomeSlider::query()->with(['imageMedia', 'translations'])->active()->ordered()->get(),
            'featuredPages' => SitePage::query()
                ->with(['featuredMedia', 'translations'])
                ->publishedVisible()
                ->featured()
                ->orderBy('sort_order')
                ->orderByDesc('updated_at')
                ->take(6)
                ->get(),
            'globalCounters' => SiteCounter::query()
                ->with('translations')
                ->active()
                ->whereNull('site_page_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'globalFaqs' => SiteFaq::query()
                ->with('translations')
                ->active()
                ->whereNull('site_page_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
