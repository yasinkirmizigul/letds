@php
    $surfaceFields = collect($field['fields'] ?? [])->keyBy('role');
    $backgroundField = $surfaceFields->get('background', []);
    $effectField = $surfaceFields->get('effect', []);
    $patternColorField = $surfaceFields->get('pattern-color', []);
    $patternField = $surfaceFields->get('pattern', []);
    $opacityField = $surfaceFields->get('opacity', []);
    $scaleField = $surfaceFields->get('scale', []);
    $blurField = $surfaceFields->get('blur', []);
    $blendField = $surfaceFields->get('blend', []);
    $storedValue = static fn (array $definition) => data_get(
        $settingValues,
        $definition['key'] ?? '',
        $definition['default'] ?? null,
    );
    $safeColor = static function (mixed $value, string $fallback): string {
        $value = strtolower(trim((string) $value));

        return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $fallback;
    };
    $safeNumber = static function (array $definition) use ($storedValue): float {
        $min = (float) ($definition['min'] ?? 0);
        $max = (float) ($definition['max'] ?? 100);
        $value = is_numeric($storedValue($definition))
            ? (float) $storedValue($definition)
            : (float) ($definition['default'] ?? $min);

        return min($max, max($min, $value));
    };
    $patternOptions = $patternField['options'] ?? [];
    $effectOptions = $effectField['options'] ?? [];
    $blendOptions = $blendField['options'] ?? [];
    $effectValue = array_key_exists((string) $storedValue($effectField), $effectOptions)
        ? (string) $storedValue($effectField)
        : (string) ($effectField['default'] ?? 'solid');
    $patternValue = array_key_exists((string) $storedValue($patternField), $patternOptions)
        ? (string) $storedValue($patternField)
        : (string) ($patternField['default'] ?? 'none');
    $blendValue = array_key_exists((string) $storedValue($blendField), $blendOptions)
        ? (string) $storedValue($blendField)
        : (string) ($blendField['default'] ?? 'soft-light');
    $backgroundValue = $safeColor($storedValue($backgroundField), (string) ($backgroundField['default'] ?? '#ffffff'));
    $patternColorValue = $safeColor($storedValue($patternColorField), (string) ($patternColorField['default'] ?? '#ffffff'));
    $opacityValue = $safeNumber($opacityField);
    $scaleValue = $safeNumber($scaleField);
    $blurValue = $safeNumber($blurField);
@endphp

<article
    class="homepage-surface-editor {{ $field['wrapper_class'] ?? '' }}"
    data-homepage-surface-editor="true"
    data-homepage-surface-effect="{{ $effectValue }}"
    style="--homepage-surface-color: {{ $backgroundValue }}; --homepage-pattern-ink: {{ $patternColorValue }}; --homepage-pattern-size: {{ $scaleValue }}px; --homepage-pattern-opacity: {{ $opacityValue / 100 }}; --homepage-pattern-blur: {{ $blurValue }}px; --homepage-pattern-blend: {{ $blendValue }}"
>
    <header class="homepage-surface-editor__header">
        <div>
            <h3>{{ $field['label'] }}</h3>
            <p>{{ $field['description'] ?? '' }}</p>
        </div>
        <span class="kt-badge kt-badge-sm kt-badge-light-primary">Canlı Önizleme</span>
    </header>

    <div class="homepage-surface-editor__preview" aria-hidden="true">
        <span class="homepage-surface-editor__pattern" data-homepage-surface-pattern="true" data-homepage-pattern="{{ $patternValue }}"></span>
        <span class="homepage-surface-editor__preview-content">
            <strong>{{ $field['label'] }}</strong>
            <small data-homepage-surface-effect-copy="true">{{ $effectValue === 'gradient' ? 'Gradyan + doku + fotoğraf karışımı' : 'Düz renk + isteğe bağlı doku' }}</small>
        </span>
    </div>

    <div class="grid gap-4 p-4 sm:p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach([$backgroundField, $patternColorField] as $colorField)
                @php
                    $colorKey = $colorField['key'];
                    $colorValue = $colorKey === ($backgroundField['key'] ?? null) ? $backgroundValue : $patternColorValue;
                    $colorRole = $colorField['role'] ?? '';
                @endphp
                <div class="grid gap-2" data-homepage-color-field="true">
                    <label class="kt-form-label" for="homepage_{{ $colorKey }}">{{ $colorField['label'] }}</label>
                    <div class="homepage-color-control flex h-11 items-stretch overflow-hidden border bg-background">
                        <input
                            type="color"
                            value="{{ $colorValue }}"
                            class="h-full w-12 shrink-0 cursor-pointer border-0 bg-transparent p-1"
                            data-homepage-color-picker="true"
                            aria-label="{{ $colorField['label'] }} renk seçici"
                        >
                        <input
                            id="homepage_{{ $colorKey }}"
                            name="settings[{{ $colorKey }}]"
                            value="{{ $colorValue }}"
                            class="min-w-0 flex-1 border-0 bg-transparent px-3 font-mono text-sm uppercase outline-none"
                            maxlength="7"
                            data-homepage-color-value="true"
                            data-homepage-surface-role="{{ $colorRole }}"
                        >
                    </div>
                    @error('settings.'.$colorKey)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>

        <fieldset class="grid gap-3">
            <legend class="sr-only">{{ $patternField['label'] ?? 'Desen' }}</legend>
            <div class="flex flex-wrap items-end justify-between gap-2">
                <span class="kt-form-label mb-0" aria-hidden="true">{{ $patternField['label'] ?? 'Desen' }}</span>
                <span class="text-xs text-muted-foreground">Fotoğraf üzerinde de çalışır</span>
            </div>
            <div class="homepage-pattern-grid">
                @foreach($patternOptions as $optionValue => $option)
                    <label class="homepage-pattern-option">
                        <input
                            type="radio"
                            name="settings[{{ $patternField['key'] }}]"
                            value="{{ $optionValue }}"
                            class="sr-only"
                            data-homepage-surface-role="pattern"
                            @checked($patternValue === (string) $optionValue)
                        >
                        <span class="homepage-pattern-swatch" data-homepage-pattern="{{ $optionValue }}" aria-hidden="true"></span>
                        <span class="homepage-pattern-option__copy">
                            <strong>{{ $option['label'] ?? $optionValue }}</strong>
                            <small>{{ $option['description'] ?? '' }}</small>
                        </span>
                        <i class="ki-filled ki-check-circle homepage-pattern-option__check" aria-hidden="true"></i>
                    </label>
                @endforeach
            </div>
            @error('settings.'.$patternField['key'])<div class="text-xs text-danger">{{ $message }}</div>@enderror
        </fieldset>

        <div class="homepage-surface-editor__range-grid">
            @foreach([$opacityField, $scaleField, $blurField] as $rangeField)
                @php
                    $rangeKey = $rangeField['key'];
                    $rangeValue = $safeNumber($rangeField);
                @endphp
                <div class="grid gap-2" data-homepage-range-field="true">
                    <div class="flex items-center justify-between gap-2">
                        <label class="kt-form-label mb-0" for="homepage_{{ $rangeKey }}">{{ $rangeField['label'] }}</label>
                        <output class="kt-badge kt-badge-sm kt-badge-light-primary" for="homepage_{{ $rangeKey }}" data-homepage-range-output="true">
                            {{ $rangeValue }}{{ $rangeField['unit'] ?? '' }}
                        </output>
                    </div>
                    <input
                        id="homepage_{{ $rangeKey }}"
                        type="range"
                        name="settings[{{ $rangeKey }}]"
                        value="{{ $rangeValue }}"
                        min="{{ $rangeField['min'] ?? 0 }}"
                        max="{{ $rangeField['max'] ?? 100 }}"
                        step="{{ $rangeField['step'] ?? 1 }}"
                        class="homepage-range-input"
                        data-homepage-range-input="true"
                        data-homepage-range-unit="{{ $rangeField['unit'] ?? '' }}"
                        data-homepage-surface-role="{{ $rangeField['role'] ?? '' }}"
                    >
                    @error('settings.'.$rangeKey)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid content-start gap-2">
                <label class="kt-form-label" for="homepage_{{ $effectField['key'] }}">{{ $effectField['label'] }}</label>
                <select
                    id="homepage_{{ $effectField['key'] }}"
                    name="settings[{{ $effectField['key'] }}]"
                    class="kt-select"
                    data-homepage-surface-role="effect"
                >
                    @foreach($effectOptions as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected($effectValue === (string) $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                <p class="text-xs leading-5 text-muted-foreground">Düz renk, alttaki fotoğrafı kapatarak seçilen zemin rengini net gösterir.</p>
                @error('settings.'.$effectField['key'])<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="grid content-start gap-2">
                <label class="kt-form-label" for="homepage_{{ $blendField['key'] }}">{{ $blendField['label'] }}</label>
                <select
                    id="homepage_{{ $blendField['key'] }}"
                    name="settings[{{ $blendField['key'] }}]"
                    class="kt-select"
                    data-homepage-surface-role="blend"
                >
                    @foreach($blendOptions as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected($blendValue === (string) $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                <p class="text-xs leading-5 text-muted-foreground">Yumuşak ışık fotoğraf ve düz renklerde en dengeli sonucu verir.</p>
                @error('settings.'.$blendField['key'])<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</article>
