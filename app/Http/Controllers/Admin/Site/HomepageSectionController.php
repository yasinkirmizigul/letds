<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\SiteHomepageSection;
use App\Models\Site\SiteHomepageSectionItem;
use App\Services\Site\HomepageSectionService;
use App\Services\Site\SiteTranslationSyncService;
use App\Support\Audit\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HomepageSectionController extends Controller
{
    public function __construct(
        private readonly SiteTranslationSyncService $translationSyncService,
        private readonly HomepageSectionService $homepageSectionService,
    ) {}

    public function index(): View
    {
        return $this->managerView('homepage');
    }

    public function services(): View
    {
        return $this->managerView('services');
    }

    private function managerView(string $placement): View
    {
        $sections = SiteHomepageSection::query()
            ->forPlacement($placement)
            ->with(['translations', 'items.translations'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.pages.site.homepage-sections.index', [
            'sections' => $sections,
            'iconOptions' => HomepageSectionService::ICON_OPTIONS,
            'surfaceOptions' => HomepageSectionService::SURFACE_OPTIONS,
            'alignmentOptions' => HomepageSectionService::ALIGNMENT_OPTIONS,
            'sectionTypeOptions' => $placement === 'services'
                ? array_intersect_key(HomepageSectionService::TYPE_OPTIONS, array_flip(['services', 'process']))
                : ['features' => HomepageSectionService::TYPE_OPTIONS['features']],
            'placement' => $placement,
            'manager' => $placement === 'services' ? [
                'badge' => 'Hizmet Sayfası İçerik Sistemi',
                'title' => 'Hizmetler Yönetimi',
                'description' => 'Hizmet kartlarını ve çalışma süreci adımlarını çok dilli olarak yönetin.',
                'create_title' => 'Yeni Hizmet Alanı',
                'create_description' => 'Hizmet kartı veya süreç adımı grubu oluşturabilirsiniz.',
                'preview_route' => 'site.services.index',
                'index_route' => 'admin.site.services.index',
                'visibility_label' => 'Bölümü hizmetler sayfasında göster',
            ] : [
                'badge' => 'Ana Sayfa İçerik Sistemi',
                'title' => 'Ana Sayfa Bölümleri',
                'description' => 'Müşteri memnuniyeti, esnek çalışma ve benzeri değer kartlarını çok dilli olarak yönetin.',
                'create_title' => 'Yeni Değer Kartları Bölümü',
                'create_description' => 'Aynı ana sayfada birden fazla kart grubu oluşturabilirsiniz.',
                'preview_route' => 'site.home',
                'index_route' => 'admin.site.homepage-sections.index',
                'visibility_label' => 'Bölümü ana sayfada göster',
            ],
            'stats' => [
                'sections' => $sections->count(),
                'active_sections' => $sections->where('is_active', true)->count(),
                'items' => $sections->sum(fn ($section) => $section->items->count()),
                'active_items' => $sections->sum(fn ($section) => $section->items->where('is_active', true)->count()),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedSection($request);

        $section = DB::transaction(function () use ($validated): SiteHomepageSection {
            $section = SiteHomepageSection::query()->create($validated['record'] + [
                'sort_order' => ((int) SiteHomepageSection::query()
                    ->forPlacement($validated['record']['placement'])
                    ->max('sort_order')) + 1,
            ]);

            $this->syncSectionTranslations($section, $validated['translations']);

            return $section;
        });

        AuditEvent::log('site.homepage.section.create', ['site_homepage_section_id' => $section->id]);

        return $this->sectionRedirect($section, 'İçerik bölümü oluşturuldu.');
    }

    public function update(Request $request, SiteHomepageSection $homepageSection): RedirectResponse
    {
        $validated = $this->validatedSection($request);

        DB::transaction(function () use ($homepageSection, $validated): void {
            $homepageSection->update($validated['record']);
            $this->syncSectionTranslations($homepageSection, $validated['translations']);
        });

        AuditEvent::log('site.homepage.section.update', ['site_homepage_section_id' => $homepageSection->id]);

        return $this->sectionRedirect($homepageSection, 'Bölüm ayarları güncellendi.');
    }

    public function destroy(SiteHomepageSection $homepageSection): RedirectResponse
    {
        $id = $homepageSection->id;
        $placement = $homepageSection->placement;
        $homepageSection->delete();

        AuditEvent::log('site.homepage.section.delete', ['site_homepage_section_id' => $id]);

        return redirect()
            ->route($placement === 'services' ? 'admin.site.services.index' : 'admin.site.homepage-sections.index')
            ->with('success', 'İçerik bölümü ve bağlı kartları silindi.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:site_homepage_sections,id'],
        ]);
        $sections = SiteHomepageSection::query()->whereKey($payload['ids'])->get(['id', 'placement']);

        if ($sections->pluck('placement')->unique()->count() !== 1) {
            throw ValidationException::withMessages([
                'ids' => 'Yalnızca aynı sayfaya ait bölümler birlikte sıralanabilir.',
            ]);
        }

        DB::transaction(function () use ($payload): void {
            foreach ($payload['ids'] as $index => $id) {
                SiteHomepageSection::query()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['ok' => true, 'message' => 'Bölüm sırası güncellendi.']);
    }

    public function storeItem(Request $request, SiteHomepageSection $homepageSection): RedirectResponse
    {
        $validated = $this->validatedItem($request);

        $item = DB::transaction(function () use ($homepageSection, $validated): SiteHomepageSectionItem {
            $item = $homepageSection->items()->create($validated['record'] + [
                'sort_order' => ((int) $homepageSection->items()->max('sort_order')) + 1,
            ]);

            $this->syncItemTranslations($item, $validated['translations']);

            return $item;
        });

        AuditEvent::log('site.homepage.section-item.create', [
            'site_homepage_section_id' => $homepageSection->id,
            'site_homepage_section_item_id' => $item->id,
        ]);

        return $this->sectionRedirect($homepageSection, 'Yeni içerik kartı eklendi.');
    }

    public function updateItem(
        Request $request,
        SiteHomepageSection $homepageSection,
        SiteHomepageSectionItem $homepageSectionItem,
    ): RedirectResponse {
        $this->assertItemBelongsToSection($homepageSection, $homepageSectionItem);
        $validated = $this->validatedItem($request);

        DB::transaction(function () use ($homepageSectionItem, $validated): void {
            $homepageSectionItem->update($validated['record']);
            $this->syncItemTranslations($homepageSectionItem, $validated['translations']);
        });

        AuditEvent::log('site.homepage.section-item.update', [
            'site_homepage_section_id' => $homepageSection->id,
            'site_homepage_section_item_id' => $homepageSectionItem->id,
        ]);

        return $this->sectionRedirect($homepageSection, 'İçerik kartı güncellendi.');
    }

    public function destroyItem(
        SiteHomepageSection $homepageSection,
        SiteHomepageSectionItem $homepageSectionItem,
    ): RedirectResponse {
        $this->assertItemBelongsToSection($homepageSection, $homepageSectionItem);
        $id = $homepageSectionItem->id;
        $homepageSectionItem->delete();

        AuditEvent::log('site.homepage.section-item.delete', [
            'site_homepage_section_id' => $homepageSection->id,
            'site_homepage_section_item_id' => $id,
        ]);

        return $this->sectionRedirect($homepageSection, 'İçerik kartı silindi.');
    }

    public function reorderItems(Request $request, SiteHomepageSection $homepageSection): JsonResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:site_homepage_section_items,id'],
        ]);
        $ids = array_map('intval', $payload['ids']);
        $ownedCount = $homepageSection->items()->whereKey($ids)->count();

        if ($ownedCount !== count($ids)) {
            throw ValidationException::withMessages([
                'ids' => 'Sıralama listesinde bu bölüme ait olmayan bir kart var.',
            ]);
        }

        DB::transaction(function () use ($homepageSection, $ids): void {
            foreach ($ids as $index => $id) {
                $homepageSection->items()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['ok' => true, 'message' => 'Kart sırası güncellendi.']);
    }

    private function validatedSection(Request $request): array
    {
        $request->mergeIfMissing([
            'placement' => 'homepage',
            'type' => 'features',
        ]);

        $validated = $request->validate([
            'placement' => ['required', Rule::in(array_keys(HomepageSectionService::PLACEMENT_OPTIONS))],
            'type' => ['required', Rule::in(array_keys(HomepageSectionService::TYPE_OPTIONS))],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:700'],
            'settings' => ['required', 'array'],
            'settings.columns' => ['required', 'integer', Rule::in([2, 3, 4, 5, 6])],
            'settings.alignment' => ['required', Rule::in(array_keys(HomepageSectionService::ALIGNMENT_OPTIONS))],
            'settings.surface' => ['required', Rule::in(array_keys(HomepageSectionService::SURFACE_OPTIONS))],
            'settings.accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.eyebrow' => ['nullable', 'string', 'max:80'],
            'translations.*.title' => ['nullable', 'string', 'max:160'],
            'translations.*.description' => ['nullable', 'string', 'max:700'],
        ]);

        if (($validated['placement'] === 'homepage' && $validated['type'] !== 'features')
            || ($validated['placement'] === 'services' && ! in_array($validated['type'], ['services', 'process'], true))) {
            throw ValidationException::withMessages([
                'type' => 'Seçilen bölüm tipi bu sayfa yerleşiminde kullanılamaz.',
            ]);
        }

        return [
            'record' => [
                'placement' => (string) $validated['placement'],
                'type' => (string) $validated['type'],
                'eyebrow' => $this->nullableText($validated['eyebrow'] ?? null),
                'title' => trim($validated['title']),
                'description' => $this->nullableText($validated['description'] ?? null),
                'settings' => [
                    'columns' => (int) $validated['settings']['columns'],
                    'alignment' => (string) $validated['settings']['alignment'],
                    'surface' => (string) $validated['settings']['surface'],
                    'accent_color' => strtolower((string) $validated['settings']['accent_color']),
                ],
                'is_active' => $request->boolean('is_active'),
            ],
            'translations' => is_array($validated['translations'] ?? null) ? $validated['translations'] : [],
        ];
    }

    private function validatedItem(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:700'],
            'icon' => ['required', Rule::in(array_keys(HomepageSectionService::ICON_OPTIONS))],
            'link_label' => ['nullable', 'string', 'max:80', 'required_with:link_url'],
            'link_url' => ['nullable', 'string', 'max:500', 'required_with:link_label'],
            'is_active' => ['nullable', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.title' => ['nullable', 'string', 'max:160'],
            'translations.*.description' => ['nullable', 'string', 'max:700'],
            'translations.*.link_label' => ['nullable', 'string', 'max:80'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $url = trim((string) $request->input('link_url'));

            if ($url !== '' && $this->homepageSectionService->safeLink($url) === null) {
                $validator->errors()->add(
                    'link_url',
                    'Bağlantı /, #, ?, http, https, mailto veya tel ile başlamalıdır.'
                );
            }
        });

        $validated = $validator->validate();

        return [
            'record' => [
                'title' => trim($validated['title']),
                'description' => trim($validated['description']),
                'icon' => (string) $validated['icon'],
                'link_label' => $this->nullableText($validated['link_label'] ?? null),
                'link_url' => $this->nullableText($validated['link_url'] ?? null),
                'is_active' => $request->boolean('is_active'),
            ],
            'translations' => is_array($validated['translations'] ?? null) ? $validated['translations'] : [],
        ];
    }

    private function syncSectionTranslations(SiteHomepageSection $section, array $translations): void
    {
        $this->translationSyncService->sync(
            $section,
            'translations',
            $translations,
            ['eyebrow', 'title', 'description']
        );
    }

    private function syncItemTranslations(SiteHomepageSectionItem $item, array $translations): void
    {
        $this->translationSyncService->sync(
            $item,
            'translations',
            $translations,
            ['title', 'description', 'link_label']
        );
    }

    private function assertItemBelongsToSection(
        SiteHomepageSection $section,
        SiteHomepageSectionItem $item,
    ): void {
        abort_unless($item->site_homepage_section_id === $section->id, 404);
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function sectionRedirect(SiteHomepageSection $section, string $message): RedirectResponse
    {
        $route = $section->placement === 'services'
            ? 'admin.site.services.index'
            : 'admin.site.homepage-sections.index';

        return redirect()
            ->to(route($route).'#section-'.$section->id)
            ->with('success', $message);
    }
}
