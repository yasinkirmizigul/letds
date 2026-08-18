@php
    $iconClasses = [
        'heart' => 'fa-heart',
        'adjustments' => 'fa-sliders',
        'chart' => 'fa-chart-line',
        'shield' => 'fa-shield-halved',
        'clock' => 'fa-clock',
        'sparkles' => 'fa-wand-magic-sparkles',
        'blueprint' => 'fa-compass-drafting',
        'document' => 'fa-file-lines',
        'report' => 'fa-chart-column',
        'health' => 'fa-heart-pulse',
        'ai' => 'fa-brain',
        'conversation' => 'fa-comments',
        'search' => 'fa-magnifying-glass',
        'support' => 'fa-life-ring',
    ];
@endphp

<i class="fa-solid {{ $iconClasses[$icon] ?? 'fa-wand-magic-sparkles' }}" aria-hidden="true"></i>
