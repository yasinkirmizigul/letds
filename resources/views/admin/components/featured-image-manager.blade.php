{{-- resources/views/admin/components/featured-image-manager.blade.php --}}
@php
    use Illuminate\Support\Str;

    $uid = $uid ?? ('fim-' . Str::random(10));
    $name = $name ?? 'featured_image'; // upload file input name
    $mediaIdName = $mediaIdName ?? 'featured_media_id'; // library seçimi için hidden input name

    $currentUrl = $currentUrl ?? null;
    $currentMediaId = $currentMediaId ?? null;

    $title = $title ?? 'Öne Çıkan Görsel';
    $hint = $hint ?? null;

    $hasImage = !empty($currentUrl);
@endphp

<div class="kt-card" data-featured-image-manager="1" data-featured-uid="{{ $uid }}">
    <div class="kt-card-header py-4">
        <h3 class="kt-card-title">{{ $title }}</h3>
    </div>

    <div class="kt-card-body grid gap-4">
        @if($hint)
            <div class="text-sm text-muted-foreground">{{ $hint }}</div>
        @endif

        {{-- hidden: library seçimi buraya yazılır --}}
        <input type="hidden"
               name="{{ $mediaIdName }}"
               value="{{ $currentMediaId }}"
               data-featured-media-id>

        <div class="grid gap-3">
            {{-- Preview --}}
            <div class="rounded-xl border border-border bg-muted/10 overflow-hidden">
                <div class="{{ $hasImage ? 'hidden' : '' }} aspect-video w-full flex items-center justify-center text-muted-foreground"
                     data-featured-placeholder>
                    <div class="grid place-items-center gap-2 py-10">
                        <i class="ki-outline ki-picture text-3xl"></i>
                        <div class="text-sm">Henüz görsel yok</div>
                    </div>
                </div>

                <img
                    data-featured-preview
                    src="{{ $hasImage ? $currentUrl : '' }}"
                    alt="featured preview"
                    class="{{ $hasImage ? '' : 'hidden' }} w-full h-full object-cover"
                >
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Upload --}}
                <input type="file"
                       name="{{ $name }}"
                       accept="image/*"
                       class="kt-input"
                       data-featured-input>

                {{-- Medyadan seç --}}
                <button type="button"
                        class="kt-btn kt-btn-light mt-4 ms-2"
                        data-media-picker="true"
                        data-media-picker-mime="image/*"
                        data-media-picker-target='[data-featured-uid="{{ $uid }}"] [data-featured-media-id]'
                        data-media-picker-preview='[data-featured-uid="{{ $uid }}"] [data-featured-preview]'>
                    <i class="ki-outline ki-folder"></i>
                    Medyadan Seç
                </button>

                {{-- Temizle --}}
                <button type="button"
                        class="kt-btn kt-btn-light mt-4"
                        data-featured-clear>
                    <i class="ki-outline ki-cross"></i>
                    Temizle
                </button>
            </div>

            <div class="text-xs text-muted-foreground">
                İki yol var: ya dosya yükle, ya kütüphaneden seç. Seçim yaptığında diğeri otomatik temizlenir.
            </div>
        </div>
    </div>
</div>
