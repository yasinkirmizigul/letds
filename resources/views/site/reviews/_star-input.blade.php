@php
    $selected = (int) ($selected ?? 0);
    $required = (bool) ($required ?? false);
@endphp

<div class="grid gap-3" data-star-rating>
    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="{{ $label ?? 'Yıldız puanı' }}">
        @foreach(range(1, 5) as $star)
            <input
                class="sr-only"
                type="radio"
                name="{{ $name }}"
                id="{{ $id }}_{{ $star }}"
                value="{{ $star }}"
                @checked($selected === $star)
                @required($required)
            >
            <label
                for="{{ $id }}_{{ $star }}"
                class="inline-flex size-12 cursor-pointer items-center justify-center rounded-lg border border-border bg-background text-3xl text-muted-foreground transition-colors hover:border-warning hover:text-warning focus-within:ring-2 focus-within:ring-primary"
                data-star-value="{{ $star }}"
                title="{{ $star }} yıldız"
            >★</label>
        @endforeach
    </div>
    <div class="text-sm text-muted-foreground" data-star-output>{{ $selected ? $selected . ' / 5' : 'Puanınızı seçin' }}</div>
</div>
