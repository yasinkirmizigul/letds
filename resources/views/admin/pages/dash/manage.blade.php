@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[92%]" data-page="dash.manage">
        @includeIf('admin.partials._flash')

        <div class="grid gap-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Kontrol Paneli Yönetimi</span>
                    <div>
                        <h1 class="text-xl font-semibold text-foreground lg:text-2xl">Kontrol paneli görünürlüğünü yönet</h1>
                        <p class="mt-2 max-w-[78ch] text-sm leading-6 text-muted-foreground">
                            Şu an kullandığın kontrol paneli bloklarını kullanıcı bazlı olarak açıp kapatabilirsin. Buradaki tercih sadece senin panel görünümünü etkiler.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-2xl app-surface-card px-4 py-3 text-sm text-muted-foreground">
                        <span class="font-semibold text-foreground">{{ $activeSectionCount }}</span> / {{ $availableSectionCount }} bileşen görünür
                    </span>
                    <a href="{{ route('admin.dashboard') }}" class="kt-btn kt-btn-light">
                        Kontrol paneline dön
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.dashboard.manage.update') }}" class="grid gap-6" data-ajax-redirect="true">
                @csrf
                @method('PUT')

                <div class="kt-card overflow-hidden">
                    <div class="kt-card-header py-5 flex-wrap gap-4">
                        <div>
                            <h3 class="kt-card-title">Kontrol paneli sırası</h3>
                            <div class="text-sm text-muted-foreground">
                                Ana blokları ve kart gruplarını tutup sürükleyerek kontrol panelinde istediğin sıraya al.
                            </div>
                        </div>

                        <span class="kt-badge kt-badge-sm kt-badge-light-primary">Sürükle bırak</span>
                    </div>

                    <div class="kt-card-content p-6">
                        <div class="grid items-start gap-6">
                            <section class="grid content-start gap-3">
                                <div>
                                    <h4 class="text-base font-semibold text-foreground">Ana blok yerleşimi</h4>
                                    <div class="text-sm text-muted-foreground">
                                        Blokları sıralayabilir veya bir diğerinin üzerine bırakarak aynı satırda gösterebilirsin.
                                    </div>
                                </div>

                                <div class="dashboard-layout-builder" data-dashboard-layout-builder>
                                    <div class="dashboard-layout-builder__hint">
                                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                            <i class="ki-filled ki-grid"></i>
                                        </span>
                                        <span>
                                            Her satır en fazla <strong>3 blok</strong> içerir. Kart tutamacı sütunlar arasında, satır tutamacı ise yalnız yukarı-aşağı taşır. Ayır butonu kartı tekli satıra çıkarır.
                                        </span>
                                    </div>

                                    <div class="sr-only" role="status" aria-live="polite" data-dashboard-layout-status></div>

                                    <div class="dashboard-layout-new-row-zone" data-dashboard-new-row-zone data-dashboard-new-row-position="before">
                                        <i class="ki-filled ki-up"></i>
                                        <span><strong>Yeni üst satır</strong> oluşturmak için kartı buraya bırak</span>
                                    </div>

                                    <div class="dashboard-layout-rows" data-dashboard-layout-rows>
                                        @foreach($dashboardLayoutRows as $rowIndex => $row)
                                            @php
                                                $rowSections = collect($row)
                                                    ->map(fn ($key) => $dashboardSectionsByKey->get($key))
                                                    ->filter()
                                                    ->values();
                                            @endphp

                                            @if($rowSections->isNotEmpty())
                                                <div class="dashboard-layout-row" data-dashboard-layout-row>
                                                    <div class="dashboard-layout-row__head">
                                                        <span class="dashboard-layout-row__title">
                                                            <button type="button" class="dashboard-layout-row__handle kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-row-handle title="Satırı sürükleyerek taşı" aria-label="Satır {{ $loop->iteration }} sırasını sürükleyerek değiştir">
                                                                <i class="ki-outline ki-menu"></i>
                                                            </button>
                                                            <span class="font-semibold text-foreground" data-dashboard-layout-row-number>Satır {{ $loop->iteration }}</span>
                                                        </span>
                                                        <span class="dashboard-layout-row__actions">
                                                            <span class="kt-badge kt-badge-sm kt-badge-light-primary" data-dashboard-layout-column-label>
                                                                {{ $rowSections->count() }} sütun
                                                            </span>
                                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-row-move="up" title="Satırı yukarı taşı" aria-label="Satır {{ $loop->iteration }} sırasını yukarı taşı">
                                                                <i class="ki-filled ki-arrow-up"></i>
                                                            </button>
                                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-row-move="down" title="Satırı aşağı taşı" aria-label="Satır {{ $loop->iteration }} sırasını aşağı taşı">
                                                                <i class="ki-filled ki-arrow-down"></i>
                                                            </button>
                                                        </span>
                                                    </div>

                                                    <div class="dashboard-layout-row__cells" data-dashboard-layout-cells data-columns="{{ $rowSections->count() }}">
                                                        @foreach($rowSections as $section)
                                                            <article
                                                                class="dashboard-layout-item"
                                                                draggable="true"
                                                                data-dashboard-layout-item
                                                                data-dashboard-section-key="{{ $section['key'] }}"
                                                            >
                                                                <input type="hidden" name="section_order[]" value="{{ $section['key'] }}">
                                                                <input type="hidden" name="layout_rows[{{ $rowIndex }}][]" value="{{ $section['key'] }}" data-dashboard-layout-input>

                                                                <div class="dashboard-layout-item__top">
                                                                    <button type="button" class="dashboard-sort-handle kt-btn kt-btn-sm kt-btn-light kt-btn-icon" title="Sürükleyerek taşı" aria-label="{{ $section['label'] }} bloğunu sürükleyerek taşı">
                                                                        <i class="ki-outline ki-menu"></i>
                                                                    </button>

                                                                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                                        <i class="{{ $section['icon'] }} text-lg"></i>
                                                                    </span>

                                                                    <span class="min-w-0 flex-1">
                                                                        <span class="block font-semibold text-foreground">{{ $section['label'] }}</span>
                                                                        <span class="mt-1 block text-xs leading-5 text-muted-foreground">{{ $section['description'] }}</span>
                                                                    </span>
                                                                </div>

                                                                <div class="dashboard-layout-item__footer">
                                                                    <span class="{{ $section['visible'] ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light' }}">
                                                                        {{ $section['visible'] ? 'Görünür' : 'Gizli' }}
                                                                    </span>

                                                                    <span class="inline-flex shrink-0 gap-1">
                                                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-layout-move="previous" title="Satırda sola taşı" aria-label="{{ $section['label'] }} bloğunu satırda sola taşı">
                                                                            <i class="ki-filled ki-arrow-left"></i>
                                                                        </button>
                                                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-layout-move="next" title="Satırda sağa taşı" aria-label="{{ $section['label'] }} bloğunu satırda sağa taşı">
                                                                            <i class="ki-filled ki-arrow-right"></i>
                                                                        </button>
                                                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light shrink-0" data-dashboard-layout-separate title="Yeni tekli satıra ayır" aria-label="{{ $section['label'] }} bloğunu yeni tekli satıra ayır">
                                                                            <i class="ki-filled ki-row-horizontal"></i>
                                                                            <span>Ayır</span>
                                                                        </button>
                                                                    </span>
                                                                </div>
                                                            </article>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>

                                    <div class="dashboard-layout-new-row-zone" data-dashboard-new-row-zone data-dashboard-new-row-position="after">
                                        <i class="ki-filled ki-down"></i>
                                        <span><strong>Yeni alt satır</strong> oluşturmak için kartı buraya bırak</span>
                                    </div>
                                </div>
                            </section>

                            <section class="grid content-start gap-3">
                                <div>
                                    <h4 class="text-base font-semibold text-foreground">Kart sırası</h4>
                                    <div class="text-sm text-muted-foreground">
                                Özet metrik ve hızlı erişim kartlarının kendi bölümleri içindeki sırasını düzenler.
                                    </div>
                                </div>

                                <div class="grid items-start gap-4 xl:grid-cols-2">
                                    @foreach($orderedDashboardSections->whereIn('key', ['kpi_overview', 'module_overview']) as $section)
                                        @if(!empty($section['children']))
                                            <div class="rounded-[22px] border border-border bg-background/70 p-4">
                                                <div class="mb-3 flex items-center justify-between gap-3">
                                                    <div class="font-semibold text-foreground">{{ $section['label'] }}</div>
                                                    <span class="kt-badge kt-badge-sm kt-badge-light">{{ count($section['children']) }} kart</span>
                                                </div>

                                                <div class="dashboard-sort-list dashboard-sort-list--compact" data-dashboard-sort-list>
                                                    @foreach($section['children'] as $child)
                                                        <div class="dashboard-sort-item dashboard-sort-item--compact" draggable="true" data-dashboard-sort-item>
                                                            <input type="hidden" name="section_order[]" value="{{ $child['key'] }}">

                                                            <button type="button" class="dashboard-sort-handle kt-btn kt-btn-sm kt-btn-light kt-btn-icon" title="Sürükle">
                                                                <i class="ki-outline ki-menu"></i>
                                                            </button>

                                                            <span class="min-w-0 flex-1">
                                                                <span class="block font-medium text-foreground">{{ $child['label'] }}</span>
                                                                <span class="mt-1 block text-xs text-muted-foreground">{{ $child['description'] }}</span>
                                                            </span>

                                                            <span class="{{ $child['visible'] ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light' }}">
                                                                {{ $child['visible'] ? 'Görünür' : 'Gizli' }}
                                                            </span>

                                                            <span class="inline-flex shrink-0 gap-1">
                                                                <button type="button" class="kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-move="up" title="Yukarı taşı">
                                                                    <i class="ki-filled ki-arrow-up"></i>
                                                                </button>
                                                                <button type="button" class="kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-move="down" title="Aşağı taşı">
                                                                    <i class="ki-filled ki-arrow-down"></i>
                                                                </button>
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                <div class="kt-card overflow-hidden">
                    <div class="kt-card-header py-5 flex-wrap gap-4">
                        <div>
                            <h3 class="kt-card-title">Görünür bloklar</h3>
                            <div class="text-sm text-muted-foreground">
                                Kapatılan bloklar kontrol paneli ana ekranından gizlenir, istediğin zaman tekrar açabilirsin.
                            </div>
                        </div>

                        <button type="submit" name="action" value="reset" class="kt-btn kt-btn-light">
                            Varsayılana dön
                        </button>
                    </div>

                    <div class="kt-card-content p-6">
                        <div class="grid gap-6">
                            @foreach($dashboardSectionGroups as $group => $sections)
                                <section class="grid gap-4">
                                    <div>
                                        <h4 class="text-base font-semibold text-foreground">{{ $group }}</h4>
                                        <div class="text-sm text-muted-foreground">
                                            Bu gruptaki blokları ayrı ayrı yönetebilirsin.
                                        </div>
                                    </div>

                                    <div class="grid gap-4 xl:grid-cols-2 2xl:grid-cols-3">
                                        @foreach($sections as $section)
                                            <div class="rounded-[28px] app-surface-card p-5 transition hover:border-primary/20 hover:shadow-sm">
                                                <div class="flex items-start gap-4">
                                                    <input
                                                        type="checkbox"
                                                        name="visible_sections[]"
                                                        value="{{ $section['key'] }}"
                                                        class="kt-checkbox mt-1"
                                                        @checked($section['visible'])
                                                    >

                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div class="flex items-center gap-3">
                                                                <span class="inline-flex size-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                                    <i class="{{ $section['icon'] }} text-lg"></i>
                                                                </span>
                                                                <div>
                                                                    <div class="font-semibold text-foreground">{{ $section['label'] }}</div>
                                                                    <div class="mt-1 text-sm text-muted-foreground">{{ $section['description'] }}</div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        @if(!empty($section['children']))
                                                            <div class="mt-4 grid gap-3 rounded-2xl bg-background/70 px-4 py-4">
                                                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                                                    Alt bileşenler
                                                                </div>

                                                                @foreach($section['children'] as $child)
                                                                    <label class="flex items-start gap-3 rounded-2xl border border-border/70 bg-background px-3 py-3">
                                                                        <input
                                                                            type="checkbox"
                                                                            name="visible_sections[]"
                                                                            value="{{ $child['key'] }}"
                                                                            class="kt-checkbox mt-1"
                                                                            @checked($child['visible'])
                                                                        >
                                                                        <span class="min-w-0">
                                                                            <span class="block font-medium text-foreground">{{ $child['label'] }}</span>
                                                                            <span class="mt-1 block text-sm text-muted-foreground">{{ $child['description'] }}</span>
                                                                        </span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="kt-btn kt-btn-light">İptal</a>
                    <button type="submit" class="kt-btn kt-btn-primary">Kontrol paneli ayarlarını kaydet</button>
                </div>
            </form>
        </div>
    </div>
@endsection
