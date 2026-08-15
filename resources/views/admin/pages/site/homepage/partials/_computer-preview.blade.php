@php
    $computerPrefix = (string) ($prefix ?? '');
    $computerPalette = [
        '--home-computer-frame' => ['key' => 'computer_frame_color', 'default' => '#1a3d59'],
        '--home-computer-detail' => ['key' => 'computer_detail_color', 'default' => '#345170'],
        '--home-computer-warm' => ['key' => 'computer_warm_color', 'default' => '#fcb515'],
        '--home-computer-neutral' => ['key' => 'computer_neutral_color', 'default' => '#a8b9bf'],
        '--home-computer-cool' => ['key' => 'computer_cool_color', 'default' => '#4687c7'],
        '--home-computer-alert' => ['key' => 'computer_alert_color', 'default' => '#ef3851'],
    ];
    $computerPreviewStyle = collect($computerPalette)->map(function (array $definition, string $property) use ($computerPrefix, $settingValues): string {
        $value = strtolower(trim((string) data_get($settingValues, $computerPrefix.$definition['key'], $definition['default'])));
        $safeValue = preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $definition['default'];

        return $property.':'.$safeValue;
    })->implode(';');
@endphp

<div
    class="homepage-computer-preview sm:col-span-2 xl:col-span-3"
    data-homepage-computer-preview="true"
    data-homepage-computer-prefix="{{ $computerPrefix }}"
    style="{{ $computerPreviewStyle }}"
>
    <div class="homepage-computer-preview__panel">
        <span class="homepage-computer-preview__label">Çizgisel görünüm</span>
        @include('site.partials.home-hero-computer', ['variant' => 'outline'])
    </div>
    <div class="homepage-computer-preview__panel">
        <span class="homepage-computer-preview__label">Dolu görünüm</span>
        @include('site.partials.home-hero-computer', ['variant' => 'solid'])
    </div>
</div>
