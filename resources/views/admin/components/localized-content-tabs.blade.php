@php
    $moduleKey = $moduleKey ?? 'content';
    $title = $title ?? 'İçerik Dilleri';
    $description = $description ?? 'Varsayılan dil ve ek dillerdeki içerikleri sekmeler halinde yönetin.';
    $languages = collect($languages ?? $siteLanguages ?? []);
    $defaultLocale = $defaultLocale ?? $siteDefaultLocale ?? $languages->first()?->code;
    $defaultLanguage = $languages->firstWhere('code', $defaultLocale) ?? $languages->first();
    $tabLanguages = $defaultLanguage
        ? collect([$defaultLanguage])->merge($languages->where('code', '!=', $defaultLocale))->values()
        : collect();
    $defaultValues = $defaultValues ?? [];
    $storedTranslations = $storedTranslations ?? [];
    $fields = $fields ?? [];
    $urlBase = rtrim((string) ($urlBase ?? url('/')), '/');
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp

<div class="kt-card overflow-hidden">
    <div class="kt-card-header py-5">
        <div>
            <h3 class="kt-card-title">{{ $title }}</h3>
            <div class="text-sm text-muted-foreground">{{ $description }}</div>
        </div>
    </div>

    <div class="kt-card-content p-6">
        @if($tabLanguages->isEmpty())
            <div class="rounded-3xl border border-dashed border-border px-6 py-10 text-center text-sm text-muted-foreground">
                Dil kaydı bulunamadı. Önce dil yönetiminden varsayılan dili oluşturun.
            </div>
        @else
            <div class="kt-tabs kt-tabs-line mb-6" data-kt-tabs="true">
                <div class="flex flex-wrap gap-2">
                    @foreach($tabLanguages as $language)
                        <button
                            type="button"
                            class="kt-tab-toggle {{ $loop->first ? 'active' : '' }}"
                            data-kt-tab-toggle="#{{ $moduleKey }}_locale_{{ $language->code }}"
                        >
                            {{ $language->native_name ?? strtoupper($language->code) }}
                            @if($language->code === $defaultLocale)
                                <span class="ms-2 kt-badge kt-badge-sm kt-badge-light-primary">Varsayılan</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach($tabLanguages as $language)
                @php
                    $isDefault = $language->code === $defaultLocale;
                    $row = $isDefault ? $defaultValues : data_get($storedTranslations, $language->code, []);
                @endphp

                <div
                    id="{{ $moduleKey }}_locale_{{ $language->code }}"
                    class="{{ $loop->first ? '' : 'hidden' }} grid gap-6"
                    @unless($isDefault) data-locale-slug-scope="true" @endunless
                >
                    <div class="rounded-[28px] app-surface-card p-5">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold text-foreground">{{ $language->native_name ?? strtoupper($language->code) }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ $isDefault ? 'Varsayılan dil içeriği' : $language->code . ' diline özel içerik' }}
                                </div>
                            </div>
                            <span class="kt-badge kt-badge-sm kt-badge-light">{{ $language->code }}</span>
                        </div>

                        <div class="grid gap-5">
                            @foreach($fields as $field)
                                @php
                                    $name = $field['name'];
                                    $type = $field['type'] ?? 'text';
                                    $label = $field['label'] ?? str($name)->headline();
                                    $placeholder = $field['placeholder'] ?? '';
                                    $value = data_get($row, $name, '');
                                    $fieldName = $isDefault ? $name : "translations[{$language->code}][{$name}]";
                                    $errorKey = $isDefault ? $name : "translations.{$language->code}.{$name}";
                                    $id = $isDefault
                                        ? ($field['id'] ?? $name)
                                        : "{$moduleKey}_{$name}_{$language->code}";
                                    $rows = $field['rows'] ?? 3;
                                    $hasError = $viewErrors->has($errorKey);
                                    $errorMessage = $viewErrors->first($errorKey);
                                @endphp

                                @if($type === 'slug')
                                    <div class="grid gap-3 rounded-3xl app-surface-card app-surface-card--soft p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <label class="kt-form-label mb-0" for="{{ $id }}">{{ $label }}</label>
                                            <span class="text-xs text-muted-foreground">Boş bırakılırsa başlıktan otomatik üretilir.</span>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <input
                                                id="{{ $id }}"
                                                name="{{ $fieldName }}"
                                                class="kt-input flex-1 {{ $hasError ? 'kt-input-invalid' : '' }}"
                                                value="{{ $value }}"
                                                placeholder="{{ $placeholder ?: 'otomatik-olusturulur' }}"
                                                @unless($isDefault) data-locale-slug="true" @endunless
                                            >
                                            <button
                                                type="button"
                                                class="kt-btn kt-btn-light"
                                                @if($isDefault) id="slug_regen" @else data-slug-regen="true" @endif
                                            >Oluştur</button>
                                            <label class="kt-switch shrink-0">
                                                <input
                                                    type="checkbox"
                                                    class="kt-switch"
                                                    @if($isDefault) id="slug_auto" @else data-slug-auto="true" @endif
                                                    @checked($value === '')
                                                >
                                                <span class="kt-switch-slider"></span>
                                            </label>
                                        </div>

                                        <div class="rounded-2xl app-surface-card px-4 py-3 text-sm text-muted-foreground">
                                            URL önizleme:
                                            <span class="font-medium text-foreground">
                                                {{ $urlBase }}{{ $isDefault ? '' : '/' . $language->code }}/<span @if($isDefault) id="url_slug_preview" @else data-slug-preview="true" @endif>{{ $value }}</span>
                                            </span>
                                        </div>

                                        @if($hasError)
                                            <div class="text-xs text-danger">{{ $errorMessage }}</div>
                                        @endif
                                    </div>
                                @elseif($type === 'textarea' || $type === 'editor')
                                    <div class="grid gap-2">
                                        <label class="kt-form-label" for="{{ $id }}">{{ $label }}</label>
                                        <textarea
                                            id="{{ $id }}"
                                            name="{{ $fieldName }}"
                                            rows="{{ $rows }}"
                                            class="kt-textarea {{ $hasError ? 'kt-input-invalid' : '' }}"
                                            placeholder="{{ $placeholder }}"
                                            @if($type === 'editor') data-localized-content-editor="true" @endif
                                        >{{ $value }}</textarea>
                                        @if($hasError)
                                            <div class="text-xs text-danger">{{ $errorMessage }}</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="grid gap-2">
                                        <label class="kt-form-label" for="{{ $id }}">{{ $label }}</label>
                                        <input
                                            id="{{ $id }}"
                                            name="{{ $fieldName }}"
                                            class="kt-input {{ $hasError ? 'kt-input-invalid' : '' }}"
                                            value="{{ $value }}"
                                            placeholder="{{ $placeholder }}"
                                            @unless($isDefault) @if(($field['slug_source'] ?? false) === true) data-locale-title="true" @endif @endunless
                                        >
                                        @if($hasError)
                                            <div class="text-xs text-danger">{{ $errorMessage }}</div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
