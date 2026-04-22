<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\SiteFaq;
use App\Models\Site\SitePage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = SiteFaq::query()
            ->with('page:id,title')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.pages.site.faqs.index', [
            'faqs' => $faqs,
            'pages' => SitePage::query()->orderBy('title')->get(['id', 'title']),
            'stats' => [
                'all' => SiteFaq::query()->count(),
                'active' => SiteFaq::query()->where('is_active', true)->count(),
                'global' => SiteFaq::query()->whereNull('site_page_id')->count(),
                'linked' => SiteFaq::query()->whereNotNull('site_page_id')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        SiteFaq::create($this->validated($request));

        return redirect()
            ->route('admin.site.faqs.index')
            ->with('success', 'SSS kaydı eklendi.');
    }

    public function update(Request $request, SiteFaq $siteFaq): RedirectResponse
    {
        $siteFaq->update($this->validated($request));

        return redirect()
            ->route('admin.site.faqs.index')
            ->with('success', 'SSS kaydı güncellendi.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:site_faqs,id'],
        ]);

        DB::transaction(function () use ($payload) {
            foreach ($payload['ids'] as $index => $id) {
                SiteFaq::query()->whereKey($id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'SSS sırası güncellendi.',
        ]);
    }

    public function destroy(SiteFaq $siteFaq): RedirectResponse
    {
        $siteFaq->delete();

        return redirect()
            ->route('admin.site.faqs.index')
            ->with('success', 'SSS kaydı silindi.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'site_page_id' => ['nullable', 'integer', 'exists:site_pages,id'],
            'group_label' => ['nullable', 'string', 'max:120'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'icon_class' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
