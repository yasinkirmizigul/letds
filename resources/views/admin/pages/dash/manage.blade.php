@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[92%]">
        @includeIf('admin.partials._flash')

        <div class="grid gap-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Dashboard Yönetimi</span>
                    <div>
                        <h1 class="text-xl font-semibold text-foreground lg:text-2xl">Dashboard görünürlüğünü yönet</h1>
                        <p class="mt-2 max-w-[78ch] text-sm leading-6 text-muted-foreground">
                            Şu an kullandığın dashboard bloklarını kullanıcı bazlı olarak açıp kapatabilirsin. Buradaki tercih sadece senin panel görünümünü etkiler.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-2xl app-surface-card px-4 py-3 text-sm text-muted-foreground">
                        <span class="font-semibold text-foreground">{{ $activeSectionCount }}</span> / {{ $availableSectionCount }} bileşen görünür
                    </span>
                    <a href="{{ route('admin.dashboard') }}" class="kt-btn kt-btn-light">
                        Dashboard'a dön
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.dashboard.manage.update') }}" class="grid gap-6">
                @csrf
                @method('PUT')

                <div class="kt-card overflow-hidden">
                    <div class="kt-card-header py-5 flex-wrap gap-4">
                        <div>
                            <h3 class="kt-card-title">Görünür bloklar</h3>
                            <div class="text-sm text-muted-foreground">
                                Kapatılan bloklar dashboard ana ekranından gizlenir, istediğin zaman tekrar açabilirsin.
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
                    <button type="submit" class="kt-btn kt-btn-primary">Dashboard ayarlarını kaydet</button>
                </div>
            </form>
        </div>
    </div>
@endsection
