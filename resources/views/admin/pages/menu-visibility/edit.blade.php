@extends('admin.layouts.main.app')

@section('content')
    @php
        $hidden = array_fill_keys($hiddenKeys, true);
    @endphp

    <div class="kt-container-fixed max-w-[92%]" data-page="menu-visibility.edit">
        @includeIf('admin.partials._flash')

        <div class="grid gap-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Süper Admin</span>
                    <div>
                        <h1 class="text-xl font-semibold text-foreground lg:text-2xl">Sol menü görünürlüğü</h1>
                        <p class="mt-2 max-w-[78ch] text-sm leading-6 text-muted-foreground">
                            Teslim edeceğin yönetim panelinde kullanılmayacak ana menüleri ve alt seçenekleri kapat. Bu ayar paneldeki tüm kullanıcılar için geçerlidir.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="border-s-2 border-primary px-4 py-2 text-sm text-muted-foreground">
                        <span class="font-semibold text-foreground" data-menu-visible-count>{{ $visibleItemCount }}</span>
                        / {{ $availableItemCount }} öğe görünür
                    </div>

                    <form method="POST" action="{{ route('admin.menu-visibility.reset') }}" data-menu-reset-form data-ajax-redirect="true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="kt-btn kt-btn-light">
                            <i class="ki-filled ki-arrow-circle-left"></i>
                            Tümünü göster
                        </button>
                    </form>
                </div>
            </div>

            <div class="kt-alert kt-alert-primary">
                <i class="ki-filled ki-shield-tick text-lg"></i>
                <div class="kt-alert-text">
                    Bu ekran yalnızca menüyü sadeleştirir. Modüllere doğrudan erişim yetkileri, Kullanıcı ve Yetki bölümündeki rol kurallarıyla yönetilmeye devam eder.
                </div>
            </div>

            <form method="POST" action="{{ route('admin.menu-visibility.update') }}" class="grid gap-6" data-ajax-redirect="true">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-4 border-y border-border py-5 lg:flex-row lg:items-center lg:justify-between">
                    <label class="relative block w-full lg:max-w-md">
                        <span class="sr-only">Menü ara</span>
                        <i class="ki-filled ki-magnifier absolute start-3 top-1/2 -translate-y-1/2 text-muted-foreground"></i>
                        <input
                            type="search"
                            class="kt-input w-full ps-10"
                            placeholder="Menü veya alt seçenek ara"
                            autocomplete="off"
                            data-menu-search
                        >
                    </label>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="kt-btn kt-btn-light" data-menu-action="show-all">
                            <i class="ki-filled ki-eye"></i>
                            Tümünü aç
                        </button>
                        <button type="button" class="kt-btn kt-btn-light" data-menu-action="hide-all">
                            <i class="ki-filled ki-eye-slash"></i>
                            Tümünü kapat
                        </button>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-2" data-menu-list>
                    @foreach($menuItems as $item)
                        @php
                            $itemKey = $item['key'];
                            $children = $item['children'] ?? [];
                            $itemVisible = !isset($hidden[$itemKey]);
                            $searchText = collect($children)
                                ->pluck('title')
                                ->prepend($item['title'] ?? '')
                                ->filter()
                                ->implode(' ');
                        @endphp

                        <section
                            class="kt-card overflow-hidden"
                            data-menu-card
                            data-menu-search-text="{{ $searchText }}"
                        >
                            <div class="kt-card-header min-h-0 gap-4 py-5">
                                <label class="flex min-w-0 flex-1 cursor-pointer items-start gap-4">
                                    <input
                                        type="checkbox"
                                        name="visible_items[]"
                                        value="{{ $itemKey }}"
                                        class="kt-checkbox mt-1"
                                        data-menu-parent-toggle
                                        @checked($itemVisible)
                                    >

                                    <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <i class="{{ $item['icon'] ?? 'ki-filled ki-menu' }} text-lg"></i>
                                    </span>

                                    <span class="min-w-0">
                                        <span class="block font-semibold text-foreground">{{ $item['title'] }}</span>
                                        <span class="mt-1 block text-sm text-muted-foreground">
                                            {{ count($children) > 0 ? count($children).' alt seçenek' : 'Tek bağlantı' }}
                                        </span>
                                    </span>
                                </label>

                                <span
                                    class="kt-badge kt-badge-sm {{ $itemVisible ? 'kt-badge-light-success' : 'kt-badge-light' }}"
                                    data-menu-status
                                >
                                    {{ $itemVisible ? 'Görünür' : 'Gizli' }}
                                </span>
                            </div>

                            @if(count($children) > 0)
                                <div class="kt-card-content p-0" data-menu-children>
                                    <div class="border-b border-border bg-muted/30 px-5 py-3 text-xs leading-5 text-muted-foreground" data-menu-parent-note @if($itemVisible) hidden @endif>
                                        Ana menü gizliyken alt seçenek tercihleri korunur ve menü tekrar açıldığında uygulanır.
                                    </div>

                                    <div class="divide-y divide-border">
                                        @foreach($children as $child)
                                            @php
                                                $childKey = $child['key'];
                                                $childVisible = !isset($hidden[$childKey]);
                                            @endphp

                                            <label class="flex cursor-pointer items-center gap-4 px-5 py-4 transition-colors hover:bg-muted/30">
                                                <input
                                                    type="checkbox"
                                                    name="visible_items[]"
                                                    value="{{ $childKey }}"
                                                    class="kt-checkbox"
                                                    data-menu-child-toggle
                                                    @checked($childVisible)
                                                >

                                                <span class="kt-menu-bullet relative flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full before:bg-border"></span>

                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-sm font-medium text-foreground">{{ $child['title'] }}</span>
                                                </span>

                                                @if(!empty($child['perm']))
                                                    <span class="hidden text-xs text-muted-foreground 2xl:inline">{{ $child['perm'] }}</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>

                <div class="hidden border border-dashed border-border py-12 text-center text-sm text-muted-foreground" data-menu-empty>
                    Aramana uygun bir menü bulunamadı.
                </div>

                <div class="sticky bottom-0 z-10 flex flex-wrap items-center justify-between gap-3 border-t border-border bg-background/95 py-4 backdrop-blur">
                    <p class="text-sm text-muted-foreground">
                        Menü Yönetimi bağlantısı güvenli geri dönüş için süper adminlerde daima görünür kalır.
                    </p>
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check"></i>
                        Menü görünürlüğünü kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
