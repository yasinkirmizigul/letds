@extends('admin.layouts.main.app')

@section('content')
    @php
        $createLocation = old('location', \App\Models\Site\SiteNavigationItem::LOCATION_PRIMARY);
        $createLinkType = old('link_type', $pages->isNotEmpty() ? \App\Models\Site\SiteNavigationItem::LINK_TYPE_PAGE : \App\Models\Site\SiteNavigationItem::LINK_TYPE_CUSTOM);
        $createTarget = old('target', \App\Models\Site\SiteNavigationItem::TARGET_SELF);
    @endphp

    <div
        class="kt-container-fixed max-w-[96%] grid gap-6"
        data-page="site.navigation.index"
        data-tree-url="{{ route('admin.site.navigation.tree') }}"
    >
        @includeIf('admin.partials._flash')

        @if($errors->any())
            <div class="kt-alert kt-alert-danger">
                <div class="kt-alert-text">
                    Menü öğesi kaydedilemedi. Lütfen form alanlarını kontrol et.
                </div>
            </div>
        @endif

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">Menü ve Navbar Yönetimi</h1>
                    <div class="text-sm text-muted-foreground">
                        İçerik sayfalarını menüye bağla, parent-child yapıyı sürükle-bırak ile kur ve aktiflik durumlarını yönet.
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Toplam Öğe</div><div class="mt-2 text-3xl font-semibold">{{ $stats['all'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Aktif</div><div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Üst Menü</div><div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['primary'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Alt Menü</div><div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['footer'] ?? 0 }}</div></div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Yeni Menü Öğesi</h3>
                        <div class="text-sm text-muted-foreground">Kök öğe olarak eklenir, sonra sürükleyerek alt menüye taşıyabilirsin.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.site.navigation.store') }}" class="kt-card-content grid gap-4 p-6">
                    @csrf

                    <div class="grid gap-2">
                        <label class="kt-form-label">Konum</label>
                        <select name="location" class="kt-select" data-kt-select="true">
                            @foreach($locationOptions as $value => $label)
                                <option value="{{ $value }}" @selected($createLocation === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('location')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Başlık</label>
                        <input name="title" class="kt-input @error('title') kt-input-invalid @enderror" value="{{ old('title') }}" placeholder="Örn. Hizmetlerimiz">
                        @error('title')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Bağlantı Türü</label>
                        <select name="link_type" class="kt-select" data-kt-select="true" data-link-type-select="true">
                            @foreach($linkTypeOptions as $value => $label)
                                <option value="{{ $value }}" @selected($createLinkType === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('link_type')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2 {{ $createLinkType === \App\Models\Site\SiteNavigationItem::LINK_TYPE_PAGE ? '' : 'hidden' }}" data-link-field="page">
                        <label class="kt-form-label">İçerik Sayfası</label>
                        <select name="site_page_id" class="kt-select @error('site_page_id') kt-input-invalid @enderror" data-kt-select="true">
                            <option value="">Sayfa seç</option>
                            @foreach($pages as $page)
                                <option value="{{ $page->id }}" @selected((int) old('site_page_id') === (int) $page->id)>
                                    {{ $page->title }}{{ !$page->is_active ? ' (Pasif)' : '' }}{{ $page->published_at && $page->published_at->isFuture() ? ' (Planlı)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if($pages->isEmpty())
                            <div class="text-xs text-warning">
                                İçerik sayfası bulunamadı. Özel URL seçerek devam edebilir veya önce içerik üretebilirsin.
                            </div>
                        @endif
                        @error('site_page_id')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2 {{ $createLinkType === \App\Models\Site\SiteNavigationItem::LINK_TYPE_CUSTOM ? '' : 'hidden' }}" data-link-field="url">
                        <label class="kt-form-label">Özel URL</label>
                        <input name="url" class="kt-input @error('url') kt-input-invalid @enderror" value="{{ old('url') }}" placeholder="https://ornek.com veya /iletisim">
                        @error('url')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">İkon</label>
                        <input name="icon_class" class="kt-input @error('icon_class') kt-input-invalid @enderror" value="{{ old('icon_class', 'ki-filled ki-arrow-right') }}">
                        @error('icon_class')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Açılış Hedefi</label>
                        <select name="target" class="kt-select" data-kt-select="true">
                            @foreach($targetOptions as $value => $label)
                                <option value="{{ $value }}" @selected($createTarget === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('target')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl app-surface-card app-surface-card--soft p-4">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="kt-checkbox mt-1" @checked(old('is_active', true))>
                        <span>
                            <span class="block font-medium text-foreground">Aktif olarak kaydet</span>
                            <span class="text-sm text-muted-foreground">Kapatırsan ön yüzde görünmez.</span>
                        </span>
                    </label>

                    <button type="submit" class="kt-btn kt-btn-primary">Menü Öğesi Kaydet</button>
                </form>
            </div>

            <div class="grid gap-6 2xl:grid-cols-2">
                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Üst Menü Ağacı</h3>
                            <div class="text-sm text-muted-foreground">Parent-child yapıyı bu alanda sürükleyerek kur.</div>
                        </div>
                    </div>

                    <div class="kt-card-content p-6">
                        <div class="grid gap-4 js-navigation-list" data-location="primary" data-root-list="true">
                            @foreach($primaryTree as $item)
                                @include('admin.pages.site.navigation._item', [
                                    'item' => $item,
                                    'pages' => $pages,
                                    'linkTypeOptions' => $linkTypeOptions,
                                    'targetOptions' => $targetOptions,
                                    'isChild' => false,
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Alt Menü Ağacı</h3>
                            <div class="text-sm text-muted-foreground">Footer bağlantılarını ayrı bir akış olarak planla.</div>
                        </div>
                    </div>

                    <div class="kt-card-content p-6">
                        <div class="grid gap-4 js-navigation-list" data-location="footer" data-root-list="true">
                            @foreach($footerTree as $item)
                                @include('admin.pages.site.navigation._item', [
                                    'item' => $item,
                                    'pages' => $pages,
                                    'linkTypeOptions' => $linkTypeOptions,
                                    'targetOptions' => $targetOptions,
                                    'isChild' => false,
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
