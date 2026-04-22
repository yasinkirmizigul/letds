<?php

namespace App\Http\Controllers\Site\Cms;

use App\Http\Controllers\Controller;
use App\Models\Site\HomeSlider;
use App\Models\Site\SiteCounter;
use App\Models\Site\SiteFaq;
use App\Models\Site\SitePage;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('site.cms.home', [
            'pageTitle' => 'Ana Sayfa',
            'sliders' => HomeSlider::query()->with('imageMedia')->active()->ordered()->get(),
            'featuredPages' => SitePage::query()
                ->with('featuredMedia')
                ->publishedVisible()
                ->featured()
                ->orderBy('sort_order')
                ->orderByDesc('updated_at')
                ->take(6)
                ->get(),
            'globalCounters' => SiteCounter::query()
                ->active()
                ->whereNull('site_page_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'globalFaqs' => SiteFaq::query()
                ->active()
                ->whereNull('site_page_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
