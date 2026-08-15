@foreach($groups as $group)
    <section class="kt-card {{ $group['wrapper_class'] ?? '' }}">
        <div class="kt-card-header py-5">
            <div>
                <h2 class="kt-card-title">{{ $group['title'] }}</h2>
                <div class="text-sm text-muted-foreground">{{ $group['description'] ?? '' }}</div>
            </div>
        </div>

        <div class="kt-card-content grid gap-4 p-4 sm:p-6 {{ $group['content_class'] ?? 'sm:grid-cols-2' }}">
            @if(($group['preview'] ?? null) === 'computer')
                @include('admin.pages.site.homepage.partials._computer-preview', [
                    'prefix' => $group['preview_prefix'] ?? '',
                    'settingValues' => $settingValues,
                ])
            @endif

            @foreach($group['fields'] as $field)
                @php
                    $key = $field['key'];
                    $value = data_get($settingValues, $key, $field['default'] ?? null);
                    $errorKey = 'settings.' . $key;
                    $wrapperClass = $field['wrapper_class'] ?? '';
                @endphp

                @if(($field['type'] ?? 'text') === 'surface')
                    @include('admin.pages.site.homepage.partials._surface-editor', [
                        'field' => $field,
                        'settingValues' => $settingValues,
                    ])
                @elseif(($field['type'] ?? 'text') === 'boolean')
                    <label class="homepage-setting-toggle {{ $wrapperClass }} flex min-h-20 items-center gap-3 border bg-background px-4 py-3">
                        <input type="hidden" name="settings[{{ $key }}]" value="0">
                        <input
                            type="checkbox"
                            name="settings[{{ $key }}]"
                            value="1"
                            class="kt-checkbox"
                            @checked((bool) $value)
                        >
                        <span class="font-medium text-foreground">{{ $field['label'] }}</span>
                    </label>
                @elseif(($field['type'] ?? 'text') === 'color')
                    <div class="{{ $wrapperClass }} grid gap-2" data-homepage-color-field="true">
                        <label class="kt-form-label" for="homepage_{{ $key }}">{{ $field['label'] }}</label>
                        <div class="homepage-color-control flex h-11 items-stretch overflow-hidden border bg-background">
                            <input
                                type="color"
                                value="{{ $value }}"
                                class="h-full w-12 shrink-0 cursor-pointer border-0 bg-transparent p-1"
                                data-homepage-color-picker="true"
                                aria-label="{{ $field['label'] }} renk seçici"
                            >
                            <input
                                id="homepage_{{ $key }}"
                                name="settings[{{ $key }}]"
                                value="{{ $value }}"
                                class="min-w-0 flex-1 border-0 bg-transparent px-3 font-mono text-sm uppercase outline-none"
                                maxlength="7"
                                data-homepage-color-value="true"
                            >
                        </div>
                        @error($errorKey)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                @elseif(($field['type'] ?? 'text') === 'select')
                    <div class="{{ $wrapperClass }} grid gap-2">
                        <label class="kt-form-label" for="homepage_{{ $key }}">{{ $field['label'] }}</label>
                        <select
                            id="homepage_{{ $key }}"
                            name="settings[{{ $key }}]"
                            class="kt-select"
                        >
                            @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>
                                    {{ $optionLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error($errorKey)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                @elseif(($field['type'] ?? 'text') === 'range')
                    <div class="{{ $wrapperClass }} grid gap-2" data-homepage-range-field="true">
                        <div class="flex items-center justify-between gap-3">
                            <label class="kt-form-label mb-0" for="homepage_{{ $key }}">{{ $field['label'] }}</label>
                            <output class="kt-badge kt-badge-sm kt-badge-light-primary" for="homepage_{{ $key }}" data-homepage-range-output="true">
                                {{ (float) $value }}{{ $field['unit'] ?? '' }}
                            </output>
                        </div>
                        <input
                            id="homepage_{{ $key }}"
                            type="range"
                            name="settings[{{ $key }}]"
                            value="{{ $value }}"
                            min="{{ $field['min'] ?? 0 }}"
                            max="{{ $field['max'] ?? 100 }}"
                            step="{{ $field['step'] ?? 1 }}"
                            class="homepage-range-input"
                            data-homepage-range-input="true"
                            data-homepage-range-unit="{{ $field['unit'] ?? '' }}"
                        >
                        <div class="flex justify-between text-xs text-muted-foreground">
                            <span>{{ $field['min'] ?? 0 }}{{ $field['unit'] ?? '' }}</span>
                            <span>{{ $field['max'] ?? 100 }}{{ $field['unit'] ?? '' }}</span>
                        </div>
                        @error($errorKey)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                @elseif(($field['type'] ?? 'text') === 'media')
                    @php
                        $previewUrl = data_get($mediaPreviews, $key . '.url');
                        $isBackground = ($field['preview'] ?? null) === 'background';
                        $uploadName = $field['upload_name'] ?? null;
                        $clearFlagName = $field['clear_flag_name'] ?? null;
                        $defaultBackgroundLightUrl = $isBackground ? data_get($backgroundDefaults ?? [], 'light.url') : null;
                        $defaultBackgroundDarkUrl = $isBackground ? data_get($backgroundDefaults ?? [], 'dark.url', $defaultBackgroundLightUrl) : null;
                        $hasDefaultBackground = filled($defaultBackgroundLightUrl);
                    @endphp
                    <div class="{{ $wrapperClass }} grid gap-3" data-homepage-media-field="true" data-homepage-media-kind="{{ $isBackground ? 'background' : 'logo' }}">
                        <label class="kt-form-label">{{ $field['label'] }}</label>
                        <input
                            id="homepage_{{ $key }}"
                            type="hidden"
                            name="settings[{{ $key }}]"
                            value="{{ $value }}"
                            data-homepage-media-input="true"
                        >
                        @if($clearFlagName)
                            <input type="hidden" name="{{ $clearFlagName }}" value="0" data-homepage-media-clear-flag="true">
                        @endif

                        <div class="homepage-logo-picker {{ $isBackground ? 'homepage-background-picker' : '' }} grid gap-4 border bg-background p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                            <div
                                class="{{ $isBackground ? 'homepage-background-preview aspect-[16/7]' : 'flex min-h-24 items-center justify-center p-4' }} {{ $isBackground && $previewUrl ? 'has-media' : ($hasDefaultBackground ? 'has-default' : '') }} relative overflow-hidden rounded-lg border border-dashed border-border bg-muted/20"
                                @if($isBackground)
                                    data-homepage-background-preview="true"
                                    data-homepage-has-default="{{ $hasDefaultBackground ? 'true' : 'false' }}"
                                    style="--homepage-preview-after: {{ data_get($settingValues, 'after_background_color', '#ec6367') }}; --homepage-preview-before: {{ data_get($settingValues, 'before_background_color', '#ffffff') }}; --homepage-preview-opacity: {{ data_get($settingValues, 'background_overlay_enabled', true) ? ((float) data_get($settingValues, 'background_overlay_opacity', 65) / 100) : 0 }}; --homepage-preview-brightness: {{ (float) data_get($settingValues, 'background_brightness', 100) }}%; --homepage-preview-position: {{ data_get($settingValues, 'background_position', 'center') }}; --homepage-default-background-light: url('{{ $defaultBackgroundLightUrl }}'); --homepage-default-background-dark: url('{{ $defaultBackgroundDarkUrl }}')"
                                @endif
                            >
                                <div class="{{ $previewUrl || $hasDefaultBackground ? 'hidden' : '' }} text-center text-sm text-muted-foreground" data-homepage-media-placeholder="true">
                                    <i class="ki-outline ki-picture mb-2 block text-2xl"></i>
                                    {{ $isBackground ? 'Henüz arka plan seçilmedi' : 'Henüz logo seçilmedi' }}
                                </div>
                                <img
                                    id="homepage_{{ $key }}_preview"
                                    src="{{ $previewUrl ?: '' }}"
                                    alt="{{ $isBackground ? 'Arka plan önizlemesi' : 'Seçili logo önizlemesi' }}"
                                    class="{{ $previewUrl ? '' : 'hidden' }} {{ $isBackground ? 'absolute inset-0 h-full w-full object-cover' : 'max-h-16 max-w-full object-contain' }}"
                                    data-homepage-media-preview="true"
                                >
                                @if($isBackground)
                                    @if($hasDefaultBackground)
                                        <span class="homepage-background-preview__default-badge {{ $previewUrl ? 'hidden' : '' }}" data-homepage-default-background-label="true">
                                            <i class="ki-outline ki-colors-square"></i>
                                            Tema duyarlı varsayılan SVG
                                        </span>
                                    @endif
                                    <span class="homepage-background-preview__overlay homepage-background-preview__overlay--after" aria-hidden="true"></span>
                                    <span class="homepage-background-preview__overlay homepage-background-preview__overlay--before" aria-hidden="true"></span>
                                    <span class="homepage-background-preview__pattern homepage-background-preview__pattern--after" data-homepage-background-pattern="after" data-homepage-pattern="none" aria-hidden="true"></span>
                                    <span class="homepage-background-preview__pattern homepage-background-preview__pattern--before" data-homepage-background-pattern="before" data-homepage-pattern="none" aria-hidden="true"></span>
                                    <span class="homepage-background-preview__divider" aria-hidden="true"></span>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2 sm:flex-col" @if($isBackground) data-homepage-background-actions="true" @endif>
                                @if(($field['allow_upload'] ?? false) && $uploadName)
                                    <label class="kt-btn kt-btn-primary cursor-pointer">
                                        <i class="ki-outline ki-cloud-add"></i>
                                        Fotoğraf Yükle
                                        <input
                                            type="file"
                                            name="{{ $uploadName }}"
                                            accept="image/jpeg,image/png,image/webp"
                                            class="hidden"
                                            data-homepage-media-file="true"
                                        >
                                    </label>
                                @endif
                                <button
                                    type="button"
                                    class="kt-btn kt-btn-light"
                                    data-media-picker="true"
                                    data-media-picker-mime="image/*"
                                    data-media-picker-target="#homepage_{{ $key }}"
                                    data-media-picker-preview="#homepage_{{ $key }}_preview"
                                >
                                    <i class="ki-outline ki-folder"></i>
                                    Medyadan Seç
                                </button>
                                <button type="button" class="kt-btn kt-btn-light" data-homepage-media-clear="true">
                                    <i class="ki-outline ki-cross"></i>
                                    Temizle
                                </button>
                            </div>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ $isBackground ? 'Özel görsel yüklemezseniz açık ve koyu moda uyumlu varsayılan SVG kullanılır. JPG, PNG ve WebP yüklemeleri WebP olarak saklanır.' : 'Şeffaf zeminli yatay veya kare logo kullanılması önerilir.' }}
                        </div>
                        @if($uploadName)
                            @error($uploadName)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                        @endif
                        @error($errorKey)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endforeach
