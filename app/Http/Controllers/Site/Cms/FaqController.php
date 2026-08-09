<?php

namespace App\Http\Controllers\Site\Cms;

use App\Http\Controllers\Controller;
use App\Models\Site\SiteFaq;
use App\Support\Site\SiteLocalization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        $locale = SiteLocalization::currentLocale();
        $search = Str::limit(trim((string) $request->query('q')), 100, '');
        $selectedGroup = Str::limit(trim((string) $request->query('group')), 120, '');

        $faqs = SiteFaq::query()
            ->with('translations')
            ->active()
            ->whereNull('site_page_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groups = $faqs
            ->map(fn (SiteFaq $faq): string => $this->groupLabel($faq, $locale))
            ->unique()
            ->values();

        $filteredFaqs = $faqs
            ->when($selectedGroup !== '', fn (Collection $items) => $items->filter(
                fn (SiteFaq $faq): bool => $this->groupLabel($faq, $locale) === $selectedGroup
            ))
            ->when($search !== '', fn (Collection $items) => $items->filter(function (SiteFaq $faq) use ($locale, $search): bool {
                $haystack = Str::lower(implode(' ', [
                    $this->groupLabel($faq, $locale),
                    (string) $faq->localized('question', $locale),
                    (string) $faq->localized('answer', $locale),
                ]));

                return Str::contains($haystack, Str::lower($search));
            }))
            ->values();

        $faqIndexUrl = SiteLocalization::localizedRoute('site.faqs.index', locale: $locale);
        $searchParameters = $search !== '' ? ['q' => $search] : [];

        return view('site.faqs.index', [
            'faqs' => $filteredFaqs,
            'faqGroups' => $groups,
            'groupedFaqs' => $filteredFaqs->groupBy(
                fn (SiteFaq $faq): string => $this->groupLabel($faq, $locale)
            ),
            'search' => $search,
            'selectedGroup' => $selectedGroup,
            'totalFaqCount' => $faqs->count(),
            'faqIndexUrl' => $faqIndexUrl,
            'allGroupsUrl' => SiteLocalization::localizedRoute('site.faqs.index', $searchParameters, $locale),
            'groupUrls' => $groups->mapWithKeys(fn (string $group): array => [
                $group => SiteLocalization::localizedRoute(
                    'site.faqs.index',
                    [...$searchParameters, 'group' => $group],
                    $locale,
                ),
            ]),
            'pageTitle' => 'Sıkça Sorulan Sorular',
            'metaDescription' => 'Hizmetler, çalışma süreci ve proje adımları hakkında sıkça sorulan soruların yanıtlarını inceleyin.',
            'canonicalUrl' => $faqIndexUrl,
        ]);
    }

    private function groupLabel(SiteFaq $faq, string $locale): string
    {
        return trim((string) $faq->localized('group_label', $locale)) ?: 'Genel';
    }
}
