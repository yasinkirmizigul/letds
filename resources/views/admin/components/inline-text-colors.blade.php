<div class="homepage-inline-text-colors" aria-label="{{ $fieldLabel }} renkleri">
    @foreach($colorFields as $colorField)
        @php
            $colorKey = (string) $colorField['key'];
            $colorLabel = (string) ($colorField['label'] ?? 'Metin rengi');
            $shortLabel = str($colorLabel)->replace([' panel', ' rengi'], '')->toString();
            $colorValue = old('settings.' . $colorKey, data_get($settingValues, $colorKey, $colorField['default'] ?? '#000000'));
            $colorErrorKey = 'settings.' . $colorKey;
        @endphp

        <label class="homepage-inline-text-color" title="{{ $colorLabel }}">
            <span>{{ $shortLabel }}</span>
            <input
                type="color"
                name="settings[{{ $colorKey }}]"
                value="{{ $colorValue }}"
                aria-label="{{ $fieldLabel }}: {{ $colorLabel }}"
                @class(['is-invalid' => $errors->has($colorErrorKey)])
            >
        </label>
    @endforeach
</div>
