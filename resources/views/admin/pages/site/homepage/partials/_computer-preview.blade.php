@php
    $computerPrefix = (string) ($prefix ?? '');
    $computerPalette = [
        '--home-computer-frame' => ['key' => 'computer_pv_body_start_color', 'default' => '#072247'],
        '--home-computer-detail' => ['key' => 'computer_pv_body_end_color', 'default' => '#0060ea'],
        '--home-computer-warm' => ['key' => 'computer_pv_bar_light_color', 'default' => '#a0c7fc'],
        '--home-computer-neutral' => ['key' => 'computer_pv_bar_mid_color', 'default' => '#7eaff8'],
        '--home-computer-cool' => ['key' => 'computer_pv_bar_vivid_color', 'default' => '#016af6'],
        '--home-computer-alert' => ['key' => 'computer_pv_bar_dark_color', 'default' => '#0046d6'],
    ];
    $computerPreviewStyle = collect($computerPalette)->map(function (array $definition, string $property) use ($computerPrefix, $settingValues): string {
        $value = strtolower(trim((string) data_get($settingValues, $computerPrefix.$definition['key'], $definition['default'])));
        $safeValue = preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $definition['default'];

        return $property.':'.$safeValue;
    });
    $computerFillMode = data_get($settingValues, $computerPrefix.'computer_pv_fill_mode', 'gradient');
    $computerGradientEnd = $computerFillMode === 'gradient'
        ? data_get($settingValues, $computerPrefix.'computer_pv_body_end_color', '#0060ea')
        : data_get($settingValues, $computerPrefix.'computer_pv_body_start_color', '#072247');
    $computerPreviewStyle = $computerPreviewStyle
        ->push('--home-computer-gradient-end:'.$computerGradientEnd)
        ->implode(';');
    $previewIdPrefix = 'admin-'.($computerPrefix !== '' ? trim($computerPrefix, '_') : 'analysis').'-pv';
@endphp

<div
    class="homepage-computer-preview sm:col-span-2 xl:col-span-3"
    data-homepage-computer-preview="true"
    data-homepage-computer-prefix="{{ $computerPrefix }}"
    style="{{ $computerPreviewStyle }}"
>
    <div class="homepage-computer-preview__panel">
        <span class="homepage-computer-preview__label">Probablue Kod Dokulu · Sabit görsel</span>
        @include('site.partials.home-hero-computer', ['variant' => 'pvt'])
    </div>
    <div class="homepage-computer-preview__panel homepage-computer-preview__panel--editable">
        <span class="homepage-computer-preview__label">Probablue Canlı Logo · Renk önizlemesi</span>
        @include('site.partials.home-hero-computer', ['variant' => 'pv', 'idPrefix' => $previewIdPrefix])
    </div>
</div>
