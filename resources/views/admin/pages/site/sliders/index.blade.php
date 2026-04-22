@extends('admin.layouts.main.app')

@section('content')
    <div
        class="kt-container-fixed max-w-[96%] grid gap-6"
        data-page="site.sliders.index"
        data-reorder-url="{{ route('admin.site.sliders.reorder') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">Ana Sayfa Slider</h1>
                    <div class="text-sm text-muted-foreground">
                        Hero alanını besleyen slaytları yönet, sıralama ve görünürlük kararlarını tek panelde ver.
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.site.sliders.create') }}" class="kt-btn kt-btn-primary">Yeni Slider</a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Toplam</div><div class="mt-2 text-3xl font-semibold">{{ $stats['all'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Aktif</div><div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Pasif</div><div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['passive'] ?? 0 }}</div></div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Slider Havuzu</h3>
                    <div class="text-sm text-muted-foreground">Kartları sürükleyerek sahne sırasını belirleyebilirsin.</div>
                </div>
            </div>

            <div id="homeSliderSortable" class="kt-card-content grid gap-4 p-6">
                @foreach($sliders as $slider)
                    <div class="rounded-[28px] app-surface-card p-5" data-id="{{ $slider->id }}">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-4">
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-light cursor-move js-sort-handle">
                                    <i class="ki-outline ki-menu"></i>
                                </button>

                                <div class="size-28 overflow-hidden rounded-3xl border border-border bg-muted/20">
                                    @if($slider->imageUrl())
                                        <img src="{{ $slider->imageUrl() }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <div class="grid h-full w-full place-items-center text-muted-foreground">
                                            <i class="ki-outline ki-picture text-2xl"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="grid gap-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="text-lg font-semibold">{{ $slider->title }}</div>
                                        <span class="kt-badge kt-badge-sm {{ $slider->is_active ? 'kt-badge-light-success' : 'kt-badge-light' }}">
                                            {{ $slider->is_active ? 'Aktif' : 'Pasif' }}
                                        </span>
                                        <span class="kt-badge kt-badge-sm kt-badge-light">{{ $themeOptions[$slider->theme] ?? $slider->theme }}</span>
                                    </div>

                                    @if($slider->subtitle)
                                        <div class="text-sm text-muted-foreground">{{ $slider->subtitle }}</div>
                                    @endif

                                    <div class="text-sm text-muted-foreground">
                                        Odak noktası: X {{ number_format($slider->crop_x, 0) }} • Y {{ number_format($slider->crop_y, 0) }} • Zoom {{ number_format($slider->crop_zoom, 2) }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('admin.site.sliders.toggleActive', $slider) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="kt-btn kt-btn-light">
                                        {{ $slider->is_active ? 'Pasifleştir' : 'Aktifleştir' }}
                                    </button>
                                </form>

                                <a href="{{ route('admin.site.sliders.edit', $slider) }}" class="kt-btn kt-btn-primary">Düzenle</a>

                                <form method="POST" action="{{ route('admin.site.sliders.destroy', $slider) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="kt-btn kt-btn-danger" onclick="return confirm('Bu slider kaydı silinsin mi?')">
                                        Sil
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
