<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\SiteNavigationItem;
use App\Models\Site\SitePage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NavigationController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.site.navigation.index', [
            'pages' => SitePage::query()
                ->orderByDesc('is_active')
                ->orderBy('title')
                ->get(['id', 'title', 'slug', 'is_active', 'published_at']),
            'primaryTree' => SiteNavigationItem::treeForLocation(SiteNavigationItem::LOCATION_PRIMARY),
            'footerTree' => SiteNavigationItem::treeForLocation(SiteNavigationItem::LOCATION_FOOTER),
            'locationOptions' => SiteNavigationItem::locationOptions(),
            'linkTypeOptions' => SiteNavigationItem::linkTypeOptions(),
            'targetOptions' => SiteNavigationItem::targetOptions(),
            'stats' => [
                'all' => SiteNavigationItem::query()->count(),
                'active' => SiteNavigationItem::query()->where('is_active', true)->count(),
                'primary' => SiteNavigationItem::query()->where('location', SiteNavigationItem::LOCATION_PRIMARY)->count(),
                'footer' => SiteNavigationItem::query()->where('location', SiteNavigationItem::LOCATION_FOOTER)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validated($request);

        $item = SiteNavigationItem::create(array_merge($payload, [
            'sort_order' => $this->nextSortOrder(
                $payload['location'],
                $payload['parent_id'] ?? null
            ),
        ]));

        return redirect()
            ->route('admin.site.navigation.index', ['highlight' => $item->id])
            ->with('success', 'Menü öğesi eklendi.');
    }

    public function update(Request $request, SiteNavigationItem $siteNavigationItem): RedirectResponse
    {
        $payload = $this->validated($request, $siteNavigationItem);
        $siteNavigationItem->update($payload);

        return redirect()
            ->route('admin.site.navigation.index', ['highlight' => $siteNavigationItem->id])
            ->with('success', 'Menü öğesi güncellendi.');
    }

    public function updateTree(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'location' => ['required', 'string'],
            'tree' => ['required', 'array'],
        ]);

        $location = (string) $payload['location'];
        if (!array_key_exists($location, SiteNavigationItem::locationOptions())) {
            throw ValidationException::withMessages([
                'location' => 'Geçersiz menü bölgesi seçildi.',
            ]);
        }

        DB::transaction(function () use ($payload, $location) {
            $sort = 1;
            foreach ($payload['tree'] as $root) {
                $rootId = (int) ($root['id'] ?? 0);
                $rootItem = SiteNavigationItem::query()->whereKey($rootId)->firstOrFail();

                $rootItem->update([
                    'location' => $location,
                    'parent_id' => null,
                    'sort_order' => $sort++,
                ]);

                $children = is_array($root['children'] ?? null) ? $root['children'] : [];
                foreach ($children as $childIndex => $child) {
                    $childId = (int) ($child['id'] ?? 0);
                    $childItem = SiteNavigationItem::query()->whereKey($childId)->firstOrFail();

                    $childItem->update([
                        'location' => $location,
                        'parent_id' => $rootItem->id,
                        'sort_order' => $childIndex + 1,
                    ]);
                }
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Menü sırası güncellendi.',
        ]);
    }

    public function toggleActive(Request $request, SiteNavigationItem $siteNavigationItem): JsonResponse|RedirectResponse
    {
        $siteNavigationItem->forceFill([
            'is_active' => !$siteNavigationItem->is_active,
        ])->save();

        if (!$request->expectsJson() && !$request->ajax()) {
            return back()->with(
                'success',
                $siteNavigationItem->is_active ? 'Menü öğesi aktifleştirildi.' : 'Menü öğesi pasifleştirildi.'
            );
        }

        return response()->json([
            'ok' => true,
            'message' => $siteNavigationItem->is_active
                ? 'Menü öğesi aktifleştirildi.'
                : 'Menü öğesi pasifleştirildi.',
            'data' => [
                'is_active' => (bool) $siteNavigationItem->is_active,
            ],
        ]);
    }

    public function destroy(SiteNavigationItem $siteNavigationItem): RedirectResponse
    {
        $siteNavigationItem->delete();

        return redirect()
            ->route('admin.site.navigation.index')
            ->with('success', 'Menü öğesi silindi.');
    }

    private function validated(Request $request, ?SiteNavigationItem $item = null): array
    {
        $validated = $request->validate([
            'location' => ['required', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:site_navigation_items,id'],
            'site_page_id' => ['nullable', 'integer', 'exists:site_pages,id'],
            'title' => ['required', 'string', 'max:120'],
            'icon_class' => ['nullable', 'string', 'max:255'],
            'link_type' => ['required', 'string'],
            'url' => ['nullable', 'string', 'max:500'],
            'target' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $location = (string) $validated['location'];
        if (!array_key_exists($location, SiteNavigationItem::locationOptions())) {
            throw ValidationException::withMessages([
                'location' => 'Geçersiz menü bölgesi seçildi.',
            ]);
        }

        $linkType = (string) $validated['link_type'];
        if (!array_key_exists($linkType, SiteNavigationItem::linkTypeOptions())) {
            throw ValidationException::withMessages([
                'link_type' => 'Geçersiz bağlantı türü seçildi.',
            ]);
        }

        $target = (string) $validated['target'];
        if (!array_key_exists($target, SiteNavigationItem::targetOptions())) {
            throw ValidationException::withMessages([
                'target' => 'Geçersiz açılış hedefi seçildi.',
            ]);
        }

        if ($linkType === SiteNavigationItem::LINK_TYPE_PAGE && empty($validated['site_page_id'])) {
            throw ValidationException::withMessages([
                'site_page_id' => 'İçerik sayfası bağlantısı için bir sayfa seçmelisin.',
            ]);
        }

        if ($linkType === SiteNavigationItem::LINK_TYPE_CUSTOM && !filled($validated['url'] ?? null)) {
            throw ValidationException::withMessages([
                'url' => 'Özel bağlantı için URL alanı zorunludur.',
            ]);
        }

        if (!empty($validated['parent_id'])) {
            $parent = SiteNavigationItem::query()->whereKey((int) $validated['parent_id'])->firstOrFail();

            if ($parent->location !== $location) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Üst menü öğesi aynı menü bölgesinde olmalıdır.',
                ]);
            }

            if ($parent->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Sadece tek seviye alt menü destekleniyor.',
                ]);
            }

            if ($item && (int) $parent->id === (int) $item->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Bir öğe kendi üst menüsü olamaz.',
                ]);
            }
        }

        return [
            'location' => $location,
            'parent_id' => $validated['parent_id'] ?? null,
            'site_page_id' => $linkType === SiteNavigationItem::LINK_TYPE_PAGE
                ? ($validated['site_page_id'] ?? null)
                : null,
            'title' => (string) $validated['title'],
            'icon_class' => $validated['icon_class'] ?? null,
            'link_type' => $linkType,
            'url' => $linkType === SiteNavigationItem::LINK_TYPE_CUSTOM ? ($validated['url'] ?? null) : null,
            'target' => $target,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function nextSortOrder(string $location, ?int $parentId = null): int
    {
        return (int) SiteNavigationItem::query()
            ->where('location', $location)
            ->where('parent_id', $parentId)
            ->max('sort_order') + 1;
    }
}
