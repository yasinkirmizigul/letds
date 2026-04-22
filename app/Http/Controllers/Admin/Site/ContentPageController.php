<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\SitePage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContentPageController extends Controller
{
    private const RESERVED_SLUGS = [
        'admin',
        'login',
        'logout',
        'member',
        'randevu-al',
        'iletisim',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status', 'all');

        $pages = SitePage::query()
            ->with('featuredMedia')
            ->withCount(['faqs', 'counters'])
            ->search($search)
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.pages.site.pages.index', [
            'pages' => $pages,
            'search' => $search,
            'status' => $status,
            'stats' => [
                'all' => SitePage::query()->count(),
                'active' => SitePage::query()->where('is_active', true)->count(),
                'featured' => SitePage::query()->where('is_featured', true)->count(),
                'published' => SitePage::query()->publishedVisible()->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.site.pages.create', [
            'page' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $page = DB::transaction(function () use ($validated) {
            return SitePage::create($this->buildPayload($validated));
        });

        $this->syncFeaturedAsset(
            $page,
            $request,
            isset($validated['featured_media_id']) ? (int) $validated['featured_media_id'] : null,
            (bool) ($validated['clear_featured_image'] ?? false)
        );

        return redirect()
            ->route('admin.site.pages.edit', $page)
            ->with('success', 'İçerik sayfası oluşturuldu.');
    }

    public function edit(SitePage $sitePage): View
    {
        $sitePage->load('featuredMedia');

        return view('admin.pages.site.pages.edit', [
            'page' => $sitePage,
        ]);
    }

    public function update(Request $request, SitePage $sitePage): RedirectResponse
    {
        $validated = $this->validatePayload($request, $sitePage);

        DB::transaction(function () use ($sitePage, $validated) {
            $sitePage->update($this->buildPayload($validated, $sitePage));
        });

        $this->syncFeaturedAsset(
            $sitePage,
            $request,
            isset($validated['featured_media_id']) ? (int) $validated['featured_media_id'] : null,
            (bool) ($validated['clear_featured_image'] ?? false)
        );

        return redirect()
            ->route('admin.site.pages.edit', $sitePage)
            ->with('success', 'İçerik sayfası güncellendi.');
    }

    public function toggleActive(Request $request, SitePage $sitePage): JsonResponse|RedirectResponse
    {
        $sitePage->forceFill([
            'is_active' => !$sitePage->is_active,
        ])->save();

        if (!$request->expectsJson() && !$request->ajax()) {
            return back()->with(
                'success',
                $sitePage->is_active ? 'İçerik sayfası aktifleştirildi.' : 'İçerik sayfası pasifleştirildi.'
            );
        }

        return response()->json([
            'ok' => true,
            'message' => $sitePage->is_active
                ? 'İçerik sayfası aktifleştirildi.'
                : 'İçerik sayfası pasifleştirildi.',
            'data' => [
                'is_active' => (bool) $sitePage->is_active,
            ],
        ]);
    }

    public function destroy(SitePage $sitePage): RedirectResponse
    {
        $this->deleteStoredImage($sitePage->featured_image_path);
        $sitePage->delete();

        return redirect()
            ->route('admin.site.pages.index')
            ->with('success', 'İçerik sayfası kaldırıldı.');
    }

    private function validatePayload(Request $request, ?SitePage $page = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'hero_kicker' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'icon_class' => ['nullable', 'string', 'max:255'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'featured_image' => ['nullable', 'image', 'max:8192'],
            'clear_featured_image' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'show_faqs' => ['nullable', 'boolean'],
            'show_counters' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'string', 'max:32'],
        ]);

        $slugSource = (string) ($validated['slug'] ?: $validated['title']);
        $normalized = Str::slug($slugSource);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'slug' => 'Geçerli bir sayfa bağlantısı üretilemedi.',
            ]);
        }

        if (in_array($normalized, self::RESERVED_SLUGS, true)) {
            throw ValidationException::withMessages([
                'slug' => 'Bu bağlantı anahtar kelimesi sistem tarafından ayrılmış durumda.',
            ]);
        }

        return $validated;
    }

    private function buildPayload(array $validated, ?SitePage $page = null): array
    {
        return [
            'title' => (string) $validated['title'],
            'slug' => $this->uniqueSlug((string) ($validated['slug'] ?: $validated['title']), $page?->id),
            'hero_kicker' => $validated['hero_kicker'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'icon_class' => $validated['icon_class'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'meta_keywords' => $validated['meta_keywords'] ?? null,
            'show_faqs' => (bool) ($validated['show_faqs'] ?? false),
            'show_counters' => (bool) ($validated['show_counters'] ?? false),
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'published_at' => $this->parseDateTime($validated['published_at'] ?? null),
        ];
    }

    private function syncFeaturedAsset(
        SitePage $page,
        Request $request,
        ?int $mediaId,
        bool $clearFeaturedImage
    ): void {
        if ($request->hasFile('featured_image')) {
            $this->deleteStoredImage($page->featured_image_path);
            $page->forceFill([
                'featured_media_id' => null,
                'featured_image_path' => $request->file('featured_image')->store('site/pages', 'public'),
            ])->save();

            return;
        }

        if ($mediaId) {
            $this->deleteStoredImage($page->featured_image_path);
            $page->forceFill([
                'featured_media_id' => $mediaId,
                'featured_image_path' => null,
            ])->save();

            return;
        }

        if ($clearFeaturedImage) {
            $this->deleteStoredImage($page->featured_image_path);
            $page->forceFill([
                'featured_media_id' => null,
                'featured_image_path' => null,
            ])->save();
        }
    }

    private function deleteStoredImage(?string $path): void
    {
        if (!$path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        foreach (['d.m.Y H:i', 'Y-m-d H:i', 'Y-m-d\\TH:i', 'd.m.Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $text);
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($text);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'published_at' => 'Yayın tarihi geçerli bir formatta olmalı.',
            ]);
        }
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'sayfa';
        $candidate = $base;
        $suffix = 2;

        while (
            SitePage::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
