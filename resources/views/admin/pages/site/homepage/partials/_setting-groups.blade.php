@foreach($groups as $group)
    <section class="kt-card">
        <div class="kt-card-header py-5">
            <div>
                <h2 class="kt-card-title">{{ $group['title'] }}</h2>
                <div class="text-sm text-muted-foreground">{{ $group['description'] ?? '' }}</div>
            </div>
        </div>

        <div class="kt-card-content grid gap-4 p-6 sm:grid-cols-2">
            @foreach($group['fields'] as $field)
                @php
                    $key = $field['key'];
                    $value = data_get($settingValues, $key, $field['default'] ?? null);
                    $errorKey = 'settings.' . $key;
                    $wrapperClass = $field['wrapper_class'] ?? '';
                @endphp

                @if(($field['type'] ?? 'text') === 'boolean')
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
                @elseif(($field['type'] ?? 'text') === 'media')
                    @php
                        $previewUrl = $key === 'header_logo_media_id' ? data_get($headerLogo, 'url') : null;
                    @endphp
                    <div class="{{ $wrapperClass }} grid gap-3" data-homepage-media-field="true">
                        <label class="kt-form-label">{{ $field['label'] }}</label>
                        <input
                            id="homepage_{{ $key }}"
                            type="hidden"
                            name="settings[{{ $key }}]"
                            value="{{ $value }}"
                            data-homepage-media-input="true"
                        >

                        <div class="homepage-logo-picker grid gap-4 border bg-background p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                            <div class="flex min-h-24 items-center justify-center overflow-hidden rounded-lg border border-dashed border-border bg-muted/20 p-4">
                                <div class="{{ $previewUrl ? 'hidden' : '' }} text-center text-sm text-muted-foreground" data-homepage-media-placeholder="true">
                                    <i class="ki-outline ki-picture mb-2 block text-2xl"></i>
                                    Henüz logo seçilmedi
                                </div>
                                <img
                                    id="homepage_{{ $key }}_preview"
                                    src="{{ $previewUrl ?: '' }}"
                                    alt="Seçili logo önizlemesi"
                                    class="{{ $previewUrl ? '' : 'hidden' }} max-h-16 max-w-full object-contain"
                                    data-homepage-media-preview="true"
                                >
                            </div>

                            <div class="flex flex-wrap gap-2 sm:flex-col">
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
                        <div class="text-xs text-muted-foreground">Şeffaf zeminli yatay veya kare logo kullanılması önerilir.</div>
                        @error($errorKey)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endforeach
