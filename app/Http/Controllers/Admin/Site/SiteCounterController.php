<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\SiteCounter;
use App\Models\Site\SitePage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteCounterController extends Controller
{
    public function index(): View
    {
        $counters = SiteCounter::query()
            ->with('page:id,title')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.pages.site.counters.index', [
            'counters' => $counters,
            'pages' => SitePage::query()->orderBy('title')->get(['id', 'title']),
            'stats' => [
                'all' => SiteCounter::query()->count(),
                'active' => SiteCounter::query()->where('is_active', true)->count(),
                'global' => SiteCounter::query()->whereNull('site_page_id')->count(),
                'linked' => SiteCounter::query()->whereNotNull('site_page_id')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        SiteCounter::create($this->validated($request));

        return redirect()
            ->route('admin.site.counters.index')
            ->with('success', 'Sayaç kaydı eklendi.');
    }

    public function update(Request $request, SiteCounter $siteCounter): RedirectResponse
    {
        $siteCounter->update($this->validated($request));

        return redirect()
            ->route('admin.site.counters.index')
            ->with('success', 'Sayaç kaydı güncellendi.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:site_counters,id'],
        ]);

        DB::transaction(function () use ($payload) {
            foreach ($payload['ids'] as $index => $id) {
                SiteCounter::query()->whereKey($id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Sayaç sırası güncellendi.',
        ]);
    }

    public function destroy(SiteCounter $siteCounter): RedirectResponse
    {
        $siteCounter->delete();

        return redirect()
            ->route('admin.site.counters.index')
            ->with('success', 'Sayaç kaydı silindi.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'site_page_id' => ['nullable', 'integer', 'exists:site_pages,id'],
            'label' => ['required', 'string', 'max:120'],
            'value' => ['required', 'integer', 'min:0'],
            'prefix' => ['nullable', 'string', 'max:30'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon_class' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
