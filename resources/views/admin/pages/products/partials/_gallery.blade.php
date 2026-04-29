@php
    $product = $product ?? null;
@endphp

@if(empty($product) || empty($product->id))
    <div class="kt-alert kt-alert-light">
        <div class="text-sm text-muted-foreground">
            Galeri eklemek için ürünü önce kaydedin.
        </div>
    </div>
@else
    @include('admin.components.gallery-manager', [
        'id' => 'product-' . $product->id,
        'title' => 'Galeriler',
        'routes' => [
            'list' => route('admin.galleries.list'),
            'index' => route('admin.products.galleries.index', $product),
            'attach' => route('admin.products.galleries.attach', $product),
            'detach' => route('admin.products.galleries.detach', $product),
            'reorder' => route('admin.products.galleries.reorder', $product),
        ],
        'slots' => [
            'main' => 'Ana',
            'sidebar' => 'Sidebar',
        ],
    ])
@endif
