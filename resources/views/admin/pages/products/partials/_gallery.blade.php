@php($product = $product ?? null)

<div class="kt-card kt-card-border" data-gallery-manager
     data-galleryable-type="{{ addslashes(\App\Models\Admin\Product\Product::class) }}"
     data-galleryable-id="{{ $product->id }}"
>
    <div class="kt-card-header">
        <h3 class="kt-card-title">Galeri</h3>
    </div>

    <div class="kt-card-content p-6 grid gap-4">
        <div class="text-sm text-muted-foreground">
            Bu panel ProjectGallery mantığıyla birebir çalışacak şekilde hazırlandı.
            Ürün-galeri route/controller’ını eklediğinde otomatik aktif olur.
        </div>

        {{-- Burayı kendi gallery-manager contract’ına göre dolduruyorsun --}}
        <div class="rounded-xl border border-border p-4 text-sm text-muted-foreground">
            Gallery Manager placeholder
        </div>
    </div>
</div>
