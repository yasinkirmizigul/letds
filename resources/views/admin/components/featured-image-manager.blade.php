{{-- resources/views/admin/components/featured-image-manager.blade.php --}}
@php
    use Illuminate\Support\Str;

    $uid = $uid ?? ('fim-' . Str::random(10));

    // Upload (Blog için)
    $fileName = $fileName ?? 'featured_image';

    // Library selected media id (Project için)
    $mediaIdName = $mediaIdName ?? 'featured_media_id';

    // initial state
    $currentUrl = $currentUrl ?? null;           // preview için url
    $currentMediaId = $currentMediaId ?? null;   // hidden media id

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

        {{-- ✅ Project için: library seçimi buraya yazılır --}}
        <input type="hidden"
               name="{{ $mediaIdName }}"
               value="{{ $currentMediaId }}"
               data-featured-media-id>

        {{-- Preview --}}
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

        <div class="flex flex-wrap items-center gap-2">
            {{-- ✅ Blog için: Upload --}}
            <input type="file"
                   name="{{ $fileName }}"
                   accept="image/*"
                   class="kt-input"
                   data-featured-input>

            {{-- ✅ Media library’den seç --}}
            <button type="button"
                    class="kt-btn kt-btn-light"
                    data-media-picker="true"
                    data-media-picker-mime="image/*"
                    data-media-picker-target='[data-featured-uid="{{ $uid }}"] [data-featured-media-id]'
                    data-media-picker-preview='[data-featured-uid="{{ $uid }}"] [data-featured-preview]'>
                <i class="ki-outline ki-folder"></i>
                Medyadan Seç
            </button>

            {{-- Temizle --}}
            <button type="button"
                    class="kt-btn kt-btn-light"
                    data-featured-clear>
                <i class="ki-outline ki-cross"></i>
                Temizle
            </button>
        </div>

        <div class="text-xs text-muted-foreground">
            Dosya yükleyebilir veya kütüphaneden seçebilirsin.
            Kütüphaneden seçim yapınca dosya seçimi temizlenir; dosya seçince de mediaId temizlenir.
        </div>
    </div>
</div>
