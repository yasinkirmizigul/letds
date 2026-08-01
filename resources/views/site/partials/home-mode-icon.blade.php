@if(($icon ?? 'chart') === 'message')
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
        <path d="M8 9h8M8 13h5" />
    </svg>
@else
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M3 3v18h18" />
        <path d="M8 17v-3M13 17V9M18 17V5" />
    </svg>
@endif
