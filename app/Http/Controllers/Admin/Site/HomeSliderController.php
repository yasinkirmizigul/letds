<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\HomeSlider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class HomeSliderController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.site.sliders.index', [
            'sliders' => HomeSlider::query()->with('imageMedia')->ordered()->get(),
            'stats' => [
                'all' => HomeSlider::query()->count(),
                'active' => HomeSlider::query()->where('is_active', true)->count(),
                'passive' => HomeSlider::query()->where('is_active', false)->count(),
            ],
            'themeOptions' => HomeSlider::themeOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.site.sliders.create', [
            'slider' => null,
            'themeOptions' => HomeSlider::themeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $slider = HomeSlider::create(array_merge($validated, [
            'sort_order' => (int) HomeSlider::query()->max('sort_order') + 1,
        ]));

        $this->syncImageAsset(
            $slider,
            $request,
            isset($validated['image_media_id']) ? (int) $validated['image_media_id'] : null,
            (bool) ($validated['clear_image'] ?? false)
        );

        return redirect()
            ->route('admin.site.sliders.edit', $slider)
            ->with('success', 'Slider kaydı oluşturuldu.');
    }

    public function edit(HomeSlider $homeSlider): View
    {
        $homeSlider->load('imageMedia');

        return view('admin.pages.site.sliders.edit', [
            'slider' => $homeSlider,
            'themeOptions' => HomeSlider::themeOptions(),
        ]);
    }

    public function update(Request $request, HomeSlider $homeSlider): RedirectResponse
    {
        $validated = $this->validated($request);

        $homeSlider->update($validated);

        $this->syncImageAsset(
            $homeSlider,
            $request,
            isset($validated['image_media_id']) ? (int) $validated['image_media_id'] : null,
            (bool) ($validated['clear_image'] ?? false)
        );

        return redirect()
            ->route('admin.site.sliders.edit', $homeSlider)
            ->with('success', 'Slider kaydı güncellendi.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:home_sliders,id'],
        ]);

        DB::transaction(function () use ($payload) {
            foreach ($payload['ids'] as $index => $id) {
                HomeSlider::query()->whereKey($id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Slider sırası güncellendi.',
        ]);
    }

    public function toggleActive(Request $request, HomeSlider $homeSlider): JsonResponse|RedirectResponse
    {
        $homeSlider->forceFill([
            'is_active' => !$homeSlider->is_active,
        ])->save();

        if (!$request->expectsJson() && !$request->ajax()) {
            return back()->with(
                'success',
                $homeSlider->is_active ? 'Slider aktifleştirildi.' : 'Slider pasifleştirildi.'
            );
        }

        return response()->json([
            'ok' => true,
            'message' => $homeSlider->is_active
                ? 'Slider aktifleştirildi.'
                : 'Slider pasifleştirildi.',
            'data' => [
                'is_active' => (bool) $homeSlider->is_active,
            ],
        ]);
    }

    public function destroy(HomeSlider $homeSlider): RedirectResponse
    {
        $this->deleteStoredImage($homeSlider->image_path);
        $homeSlider->delete();

        return redirect()
            ->route('admin.site.sliders.index')
            ->with('success', 'Slider kaydı silindi.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'image_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'image' => ['nullable', 'image', 'max:8192'],
            'clear_image' => ['nullable', 'boolean'],
            'crop_x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'crop_y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'crop_zoom' => ['nullable', 'numeric', 'min:1', 'max:2.5'],
            'overlay_strength' => ['nullable', 'integer', 'min:0', 'max:90'],
            'theme' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!array_key_exists((string) $validated['theme'], HomeSlider::themeOptions())) {
            throw ValidationException::withMessages([
                'theme' => 'Geçersiz slider teması seçildi.',
            ]);
        }

        return $validated + [
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function syncImageAsset(
        HomeSlider $slider,
        Request $request,
        ?int $mediaId,
        bool $clearImage
    ): void {
        if ($request->hasFile('image')) {
            $this->deleteStoredImage($slider->image_path);
            $slider->forceFill([
                'image_media_id' => null,
                'image_path' => $request->file('image')->store('site/sliders', 'public'),
            ])->save();

            return;
        }

        if ($mediaId) {
            $this->deleteStoredImage($slider->image_path);
            $slider->forceFill([
                'image_media_id' => $mediaId,
                'image_path' => null,
            ])->save();

            return;
        }

        if ($clearImage) {
            $this->deleteStoredImage($slider->image_path);
            $slider->forceFill([
                'image_media_id' => null,
                'image_path' => null,
            ])->save();
        }
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
