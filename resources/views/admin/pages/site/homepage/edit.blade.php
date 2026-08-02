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

        <form method="POST" action="{{ route('admin.site.homepage.update') }}" enctype="multipart/form-data" class="grid gap-6" data-native-submit="true">
            @csrf
            @method('PUT')

            <section class="kt-card overflow-hidden">
                <div class="kt-card-header py-5">
                    <div>
                        <h2 class="kt-card-title">Ana Sayfa Sekmeleri</h2>
                        <div class="text-sm text-muted-foreground">Her sekmenin metinlerini, bağlantılarını ve renklerini ayrı yönetin.</div>
                    </div>
                </div>
                <div class="kt-card-content p-4 sm:p-6">
                    <div class="homepage-admin-mode-tabs" role="tablist" aria-label="Ana sayfa sekmesi seçimi">
                        @foreach($modes as $key => $mode)
                            @php
                                $modeLabel = data_get($defaultValues, $mode['label_key'], $mode['label']);
                            @endphp
                            <button
                                type="button"
                                class="homepage-admin-mode-tab {{ $loop->first ? 'is-active' : '' }}"
                                role="tab"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                aria-controls="homepage_admin_mode_{{ $key }}"
                                data-homepage-admin-mode-tab="{{ $key }}"
                                data-homepage-admin-mode-label-key="{{ $mode['label_key'] }}"
                                data-homepage-admin-mode-default-label="{{ $mode['label'] }}"
                            >
                                <span class="homepage-admin-mode-tab__icon">
                                    <i class="ki-filled {{ ($mode['icon'] ?? 'chart') === 'message' ? 'ki-messages' : 'ki-chart-simple' }}"></i>
                                </span>
                                <span data-homepage-admin-mode-label="true">{{ $modeLabel }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>

            @foreach($modes as $key => $mode)
                <div
                    id="homepage_admin_mode_{{ $key }}"
                    class="{{ $loop->first ? '' : 'hidden' }} grid gap-6"
                    role="tabpanel"
                    data-homepage-admin-mode-panel="{{ $key }}"
                >
                    @include('admin.components.localized-content-tabs', [
                        'moduleKey' => 'site_homepage_' . $key,
                        'title' => $mode['label'] . ' İçeriği',
                        'description' => 'Bu sekmenin üst menü etiketi, ana mesajı ve buton bilgileri.',
                        'defaultValues' => $defaultValues,
                        'storedTranslations' => $translationValues,
                        'settingValues' => $settingValues,
                        'fields' => $modeLocalizedFields[$key] ?? [],
                        'contentGridClass' => 'grid gap-5 lg:grid-cols-2',
                    ])

                    <div class="grid gap-6 xl:grid-cols-2">
                        @include('admin.pages.site.homepage.partials._setting-groups', [
                            'groups' => $modeSettingGroups[$key] ?? [],
                            'settingValues' => $settingValues,
                            'mediaPreviews' => $mediaPreviews,
                        ])
                    </div>
                </div>
            @endforeach

            @include('admin.components.localized-content-tabs', [
                'moduleKey' => 'site_homepage_shared',
                'title' => 'Ortak Ana Sayfa İçeriği',
                'description' => 'Tarayıcı başlığı ve iki sekmede ortak kullanılan bilgi noktaları.',
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
