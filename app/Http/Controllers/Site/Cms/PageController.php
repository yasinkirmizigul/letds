<?php

namespace App\Http\Controllers\Site\Cms;

use App\Http\Controllers\Controller;
use App\Models\Site\SitePage;
use App\Support\Site\SiteLocalization;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $locale = SiteLocalization::currentLocale();
        $isDefault = SiteLocalization::isDefault($locale);

        $page = SitePage::query()
            ->with([
                'featuredMedia',
                'translations',
                'faqs' => fn ($query) => $query->with('translations')->active()->orderBy('sort_order')->orderBy('id'),
                'counters' => fn ($query) => $query->with('translations')->active()->orderBy('sort_order')->orderBy('id'),
            ])
            ->publishedVisible()
            ->where(function (Builder $query) use ($slug, $locale, $isDefault) {
                $query->where('slug', $slug);

                if (!$isDefault) {
                    $query->orWhereHas('translations', function (Builder $translationQuery) use ($slug, $locale) {
                        $translationQuery
                            ->where('locale', $locale)
                            ->where('slug', $slug);
                    });
                }
            })
            ->firstOrFail();

        return view('site.cms.page', [
            'page' => $page,
            'currentSitePage' => $page,
            'pageTitle' => $page->localized('meta_title') ?: $page->localized('title'),
        ]);
    }
}
