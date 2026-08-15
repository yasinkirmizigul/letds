@php
    $computerVariant = ($variant ?? 'solid') === 'outline' ? 'outline' : 'solid';
@endphp

@if($computerVariant === 'outline')
    <svg
        class="home-hero-computer home-hero-computer--outline"
        viewBox="0 0 103.55 104.68"
        aria-hidden="true"
        focusable="false"
        data-home-computer-variant="outline"
    >
        <g fill="none" stroke-miterlimit="10">
            <path stroke="var(--home-computer-frame)" d="M63,87.77c-2.76-.35-6.87-.78-11.08-.78s-8.11.38-11.06.74l-.31-13.05h22.45v13.1h0Z" />
            <path stroke="var(--home-computer-frame)" d="M13.7.5h76.16c7.29,0,13.2,5.91,13.2,13.2v47.78c0,7.29-5.91,13.2-13.2,13.2H13.7c-7.29,0-13.2-5.91-13.2-13.2V13.7C.5,6.41,6.41.5,13.7.5Z" />
            <rect stroke="var(--home-computer-frame)" x="8.56" y="8.23" width="86.42" height="58.24" rx="6.22" />
            <path stroke="var(--home-computer-frame)" d="M93.37,104.18v-6.07c-25.75-14.82-57.44-14.82-83.18,0v6.07h83.18Z" />
            <rect stroke="var(--home-computer-warm)" x="24.34" y="44.13" width="5.84" height="18.33" />
            <rect stroke="var(--home-computer-detail)" x="34.34" y="36.32" width="5.84" height="26.14" />
            <rect stroke="var(--home-computer-neutral)" x="44.33" y="28.32" width="5.84" height="34.14" />
            <rect stroke="var(--home-computer-cool)" x="54.33" y="32.71" width="5.84" height="29.75" />
            <rect stroke="var(--home-computer-alert)" x="64.33" y="22.17" width="5.84" height="40.29" />
            <circle stroke="var(--home-computer-detail)" cx="84.4" cy="37.59" r="6.82" />
            <path stroke="var(--home-computer-neutral)" d="M84.4,37.59v-6.82c-1.88,0-3.59.76-4.82,2l4.82,4.82Z" />
        </g>
        <g fill="var(--home-computer-detail)">
            <rect x="16.06" y="14.13" width="1.64" height="46.91" />
            <rect x="12.57" y="61.04" width="8.61" height="1.42" />
            <rect x="12.57" y="14.13" width="8.61" height="1.42" />
        </g>
    </svg>
@else
    <svg
        class="home-hero-computer home-hero-computer--solid"
        viewBox="0 0 101.84 103.47"
        aria-hidden="true"
        focusable="false"
        data-home-computer-variant="solid"
    >
        <g fill="var(--home-computer-frame)">
            <rect x="39.76" y="70.67" width="22.45" height="27.25" />
            <path d="M92.58,103.47v-6.07c-25.75-14.82-57.44-14.82-83.18,0v6.07h83.18Z" />
            <path d="M87.72,9c2.82,0,5.12,2.3,5.12,5.12v45.42c0,2.82-2.3,5.12-5.12,5.12H14.12c-2.82,0-5.12-2.3-5.12-5.12V14.12c0-2.82,2.3-5.12,5.12-5.12h73.6M87.72,0H14.12C6.32,0,0,6.32,0,14.12v45.42c0,7.8,6.32,14.12,14.12,14.12h73.6c7.8,0,14.12-6.32,14.12-14.12V14.12c0-7.8-6.32-14.12-14.12-14.12h0Z" />
        </g>
        <rect x="6.98" y="6.98" width="87.89" height="59.7" rx="10.26" fill="none" stroke="var(--home-computer-frame)" stroke-miterlimit="10" />
        <g fill="var(--home-computer-detail)">
            <rect x="15.27" y="13.42" width="1.64" height="46.91" />
            <rect x="11.78" y="60.33" width="8.61" height="1.42" />
            <rect x="11.78" y="13.42" width="8.61" height="1.42" />
            <rect x="33.55" y="35.61" width="5.84" height="26.14" />
            <circle cx="83.61" cy="36.88" r="6.82" />
        </g>
        <rect fill="var(--home-computer-warm)" x="23.55" y="43.42" width="5.84" height="18.33" />
        <rect fill="var(--home-computer-neutral)" x="43.54" y="27.61" width="5.84" height="34.14" />
        <rect fill="var(--home-computer-cool)" x="53.54" y="32" width="5.84" height="29.75" />
        <rect fill="var(--home-computer-alert)" x="63.54" y="21.46" width="5.84" height="40.29" />
        <path fill="var(--home-computer-neutral)" d="M83.61,36.88v-6.82c-1.88,0-3.59.76-4.82,2l4.82,4.82Z" />
    </svg>
@endif
