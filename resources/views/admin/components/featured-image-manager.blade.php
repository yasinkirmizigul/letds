{{-- resources/views/admin/components/featured-image-manager.blade.php --}}
@php
    use Illuminate\Support\Str;

    $uid = $uid ?? ('fim-' . Str::random(10));

    $fileName = $fileName ?? 'featured_image';
    $mediaIdName = $mediaIdName ?? 'featured_media_id';
    $clearFlagName = $clearFlagName ?? 'clear_featured_image';

    $currentUrl = $currentUrl ?? null;
    $currentMediaId = $currentMediaId ?? null;

    $title = $title ?? 'Öne Çıkan Görsel';
    $hint = $hint ?? null;

    $hasImage = !empty($currentUrl);
@endphp
<div class="kt-card"
     data-featured-image-manager="1"
     data-featured-uid="{{ $uid }}">
    <div class="kt-card-header py-4">
        <h3 class="kt-card-title">{{ $title }}</h3>
    </div>

    <div class="kt-card-body grid gap-4">
        @if($hint)
            <div class="text-sm text-muted-foreground">{{ $hint }}</div>
        @endif

        <input type="hidden"
               name="{{ $mediaIdName }}"
               value="{{ $currentMediaId }}"
               data-featured-media-id>

        <input type="hidden"
               name="{{ $clearFlagName }}"
               value="0"
               data-featured-clear-flag>

        <div class="rounded-xl border border-border bg-muted/10 overflow-hidden">
            <div class="{{ $hasImage ? 'hidden' : '' }} aspect-video w-full flex items-center justify-center text-muted-foreground"
                 data-featured-placeholder>
                <div class="grid place-items-center gap-2 py-10">
                    <i class="ki-outline ki-picture text-3xl"></i>
                    <div class="text-sm">Henüz görsel yok</div>
                </div>
            </div>

            <img data-featured-preview
                 src="{{ $hasImage ? $currentUrl : '' }}"
                 alt="featured preview"
                 class="{{ $hasImage ? '' : 'hidden' }} w-full h-full object-cover">
        </div>

        <div class="flex flex-wrap items-center gap-2 p-5">
            <input type="file"
                   name="{{ $fileName }}"
                   accept="image/*"
                   class="kt-input mb-3"
                   data-featured-input>

            <button type="button"
                    class="kt-btn kt-btn-light"
                    data-media-picker="true"
                    data-media-picker-mime="image/*"
                    data-media-picker-target='[data-featured-uid="{{ $uid }}"] [data-featured-media-id]'
                    data-media-picker-preview='[data-featured-uid="{{ $uid }}"] [data-featured-preview]'>
                <i class="ki-outline ki-folder"></i>
                Medyadan Seç
            </button>

            <button type="button"
                    class="kt-btn kt-btn-light"
                    data-featured-clear>
                <i class="ki-outline ki-cross"></i>
                Temizle
            </button>
        </div>

        <div class="text-xs text-muted-foreground p-3">
            Dosya yükleyebilir veya kütüphaneden seçim yapabilirsin.
            Kütüphaneden seçim yapınca dosya seçimi temizlenir; dosya seçince de mediaId temizlenir.
        </div>
    </div>
</div>
