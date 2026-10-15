@extends('admin.layouts.main.app')

@section('content')
    @php
        $defaultValues = old() ? array_replace($content, collect(old())->only(array_keys($content))->all()) : $content;
        $translationValues = old('translations', $storedTranslations);
        $settingValues = old('settings', $settings);
    @endphp

    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="site.homepage.edit">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">{{ $schema['title'] ?? 'Ana Sayfa Yönetimi' }}</h1>
                    <div class="text-sm text-muted-foreground">{{ $schema['description'] ?? '' }}</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.site.homepage-sections.index') }}" class="kt-btn kt-btn-light-primary">
                    <i class="ki-filled ki-grid text-base"></i>
                    İçerik Bölümleri
                </a>
                <a
                    href="{{ route('site.home') }}"
                    target="_blank"
                    rel="noopener"
                    class="kt-btn kt-btn-light"
                    title="Ana sayfayı aç"
                >
                    <i class="ki-filled ki-eye text-base"></i>
                    Siteyi Gör
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.site.homepage.update') }}" enctype="multipart/form-data" class="grid gap-6" data-native-submit="true">
            @csrf
            @method('PUT')

            <section class="kt-card overflow-hidden">
                <div class="kt-card-header py-5">
                    <div>
                        <h2 class="kt-card-title">Giriş Ekranı Düzeni</h2>
                        <div class="text-sm text-muted-foreground">Giriş artık sekmeli değil; iki yan yana alan olarak çalışıyor.</div>
                    </div>
                </div>
                <div class="kt-card-content grid gap-4 p-4 sm:grid-cols-2 sm:p-6">
                    <div class="rounded-xl border border-primary/35 bg-primary/5 p-4">
                        <div class="font-semibold text-foreground">Sol Alan · İstatistiksel Analiz ve Danışmanlık</div>
                        <p class="mt-1 text-sm text-muted-foreground">Başlık, yönlendirme butonu ve iki tarafın görünüm renkleri aşağıdaki ayarlardan yönetilir.</p>
                    </div>
                    <div class="rounded-xl border border-border bg-background p-4">
                        <div class="font-semibold text-foreground">Sağ Alan · Probablue Studio</div>
                        <p class="mt-1 text-sm text-muted-foreground">Yakında açılacak pasif alan. Eski “İstatistiksel Danışmanlık” sekmesi artık kullanılmıyor; kayıtlı eski içerikler korunur.</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6">
                @include('admin.components.localized-content-tabs', [
                    'moduleKey' => 'site_homepage_analysis',
                    'title' => 'Sol Alan İçeriği',
                    'description' => 'Analiz başlığı, buton metni ve bağlantısı. Sağdaki Studio alanı şimdilik sabittir.',
                    'defaultValues' => $defaultValues,
                    'storedTranslations' => $translationValues,
                    'settingValues' => $settingValues,
                    'fields' => $modeLocalizedFields['analysis'] ?? [],
                    'contentGridClass' => 'grid gap-5 lg:grid-cols-2',
                ])

                <div class="grid gap-6 xl:grid-cols-2">
                    @include('admin.pages.site.homepage.partials._setting-groups', [
                        'groups' => $modeSettingGroups['analysis'] ?? [],
                        'settingValues' => $settingValues,
                        'mediaPreviews' => $mediaPreviews,
                        'backgroundDefaults' => $backgroundDefaults,
                    ])
                </div>
            </div>

            @include('admin.components.localized-content-tabs', [
                'moduleKey' => 'site_homepage_shared',
                'title' => 'Ortak Ana Sayfa İçeriği',
                'description' => 'Tarayıcı başlığı ve girişteki ortak bilgi noktaları.',
                'defaultValues' => $defaultValues,
                'storedTranslations' => $translationValues,
                'settingValues' => $settingValues,
                'fields' => $sharedLocalizedFields,
                'contentGridClass' => 'grid gap-5 lg:grid-cols-2',
            ])

            <div class="grid gap-6 xl:grid-cols-2">
                @include('admin.pages.site.homepage.partials._setting-groups', [
                    'groups' => $sharedSettingGroups,
                    'settingValues' => $settingValues,
                    'mediaPreviews' => $mediaPreviews,
                    'backgroundDefaults' => $backgroundDefaults,
                ])
            </div>

            <div class="flex justify-end border-t border-border pt-5">
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check text-base"></i>
                    Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    </div>
@endsection
