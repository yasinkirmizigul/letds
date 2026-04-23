<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\HomeSliderTranslation;
use App\Models\Site\SiteCounterTranslation;
use App\Models\Site\SiteFaqTranslation;
use App\Models\Site\SiteLanguage;
use App\Models\Site\SiteNavigationItemTranslation;
use App\Models\Site\SitePageTranslation;
use App\Models\Site\SiteSettingTranslation;
use App\Services\Site\SiteDefaultLocalePromotionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SiteLanguageController extends Controller
{
    public function __construct(
        private readonly SiteDefaultLocalePromotionService $defaultLocalePromotionService,
    ) {}

    public function index(): View
    {
        $languages = SiteLanguage::query()
            ->ordered()
            ->get();

        return view('admin.pages.site.languages.index', [
            'languages' => $languages,
            'stats' => [
                'all' => $languages->count(),
                'active' => $languages->where('is_active', true)->count(),
                'rtl' => $languages->where('is_rtl', true)->count(),
                'default' => $languages->firstWhere('is_default', true),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated, $request) {
            $language = SiteLanguage::query()->create($validated);

            if ($request->boolean('make_default')) {
                $this->defaultLocalePromotionService->promote($language);
            }
        });

        return redirect()
            ->route('admin.site.languages.index')
            ->with('success', 'Site dili eklendi.');
    }

    public function update(Request $request, SiteLanguage $siteLanguage): RedirectResponse
    {
        $validated = $this->validated($request, $siteLanguage);

        DB::transaction(function () use ($siteLanguage, $validated, $request) {
            $siteLanguage->update($validated);

            if ($request->boolean('make_default')) {
                $this->defaultLocalePromotionService->promote($siteLanguage->fresh());
            }
        });

        return redirect()
            ->route('admin.site.languages.index')
            ->with('success', 'Site dili güncellendi.');
    }

    public function toggleActive(SiteLanguage $siteLanguage): RedirectResponse
    {
        if ($siteLanguage->is_default) {
            throw ValidationException::withMessages([
                'language' => 'Varsayılan dil pasifleştirilemez.',
            ]);
        }

        if ($siteLanguage->is_active && SiteLanguage::query()->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'language' => 'En az bir aktif dil kalmalıdır.',
            ]);
        }

        $siteLanguage->forceFill([
            'is_active' => !$siteLanguage->is_active,
        ])->save();

        return redirect()
            ->route('admin.site.languages.index')
            ->with('success', $siteLanguage->is_active ? 'Dil aktifleştirildi.' : 'Dil pasifleştirildi.');
    }

    public function makeDefault(SiteLanguage $siteLanguage): RedirectResponse
    {
        $this->defaultLocalePromotionService->promote($siteLanguage);

        return redirect()
            ->route('admin.site.languages.index')
            ->with('success', 'Varsayılan site dili güncellendi.');
    }

    public function destroy(SiteLanguage $siteLanguage): RedirectResponse
    {
        if ($siteLanguage->is_default) {
            throw ValidationException::withMessages([
                'language' => 'Varsayılan dil silinemez.',
            ]);
        }

        if (SiteLanguage::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'language' => 'En az bir dil tanımlı kalmalıdır.',
            ]);
        }

        DB::transaction(function () use ($siteLanguage) {
            $locale = $siteLanguage->code;

            SitePageTranslation::query()->where('locale', $locale)->delete();
            SiteFaqTranslation::query()->where('locale', $locale)->delete();
            SiteCounterTranslation::query()->where('locale', $locale)->delete();
            SiteNavigationItemTranslation::query()->where('locale', $locale)->delete();
            SiteSettingTranslation::query()->where('locale', $locale)->delete();
            HomeSliderTranslation::query()->where('locale', $locale)->delete();

            $siteLanguage->delete();
        });

        return redirect()
            ->route('admin.site.languages.index')
            ->with('success', 'Site dili silindi.');
    }

    private function validated(Request $request, ?SiteLanguage $siteLanguage = null): array
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                'regex:/^[a-zA-Z]{2}(?:-[a-zA-Z]{2})?$/',
                Rule::unique('site_languages', 'code')->ignore($siteLanguage?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'native_name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_rtl' => ['nullable', 'boolean'],
            'make_default' => ['nullable', 'boolean'],
        ]);

        return [
            'code' => $this->normalizeLocaleCode((string) $validated['code']),
            'name' => trim((string) $validated['name']),
            'native_name' => trim((string) $validated['native_name']),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
            'is_rtl' => $request->boolean('is_rtl'),
        ];
    }

    private function normalizeLocaleCode(string $code): string
    {
        $code = trim($code);

        if (!str_contains($code, '-')) {
            return strtolower($code);
        }

        [$language, $region] = array_pad(explode('-', $code, 2), 2, null);

        return strtolower((string) $language) . '-' . strtoupper((string) $region);
    }
}
