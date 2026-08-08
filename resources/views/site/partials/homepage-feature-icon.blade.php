@switch($icon ?? 'sparkles')
    @case('heart')
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z" />
        </svg>
        @break
    @case('adjustments')
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 7h10M18 7h2M14 4v6M4 17h2M10 17h10M10 14v6" />
        </svg>
        @break
    @case('chart')
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 19V9M10 19V5M16 19v-7M22 19V3M2 19h20" />
        </svg>
        @break
    @case('shield')
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3 4.5 6v5.2c0 4.6 3.1 8.8 7.5 9.8 4.4-1 7.5-5.2 7.5-9.8V6L12 3Z" />
            <path d="m8.8 12 2.1 2.1 4.5-4.5" />
        </svg>
        @break
    @case('clock')
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3.5 2" />
        </svg>
        @break
    @default
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="m12 3 1.4 4.4L18 9l-4.6 1.6L12 15l-1.4-4.4L6 9l4.6-1.6L12 3Z" />
            <path d="m18.5 15 .7 2.3 2.3.7-2.3.8-.7 2.2-.8-2.2-2.2-.8 2.2-.7.8-2.3Z" />
            <path d="m5 13 .7 2.2L8 16l-2.3.8L5 19l-.8-2.2L2 16l2.2-.8L5 13Z" />
        </svg>
@endswitch
