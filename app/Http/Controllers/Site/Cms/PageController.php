<?php

namespace App\Http\Controllers\Site\Cms;

use App\Http\Controllers\Controller;
use App\Models\Site\SitePage;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = SitePage::query()
            ->with([
                'featuredMedia',
                'faqs' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('id'),
                'counters' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('id'),
            ])
            ->publishedVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('site.cms.page', [
            'page' => $page,
            'pageTitle' => $page->meta_title ?: $page->title,
        ]);
    }
}
