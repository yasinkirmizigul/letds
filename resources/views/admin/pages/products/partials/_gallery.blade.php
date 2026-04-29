@if(empty($product) || empty($product->id))
    <div class="kt-card">
        <div class="kt-card-content p-5">
            <div class="rounded-2xl border border-dashed border-border bg-background/75 px-4 py-4 text-sm text-muted-foreground">
                Galeri eklemek için ürünü önce kaydedin. Kayıttan sonra galerileri ana alan ve yan alan olarak bağlayabilirsiniz.
            </div>
        </div>
    </div>
@else
    @include('admin.components.gallery-manager', [
        'id' => 'product-' . $product->id,
        'title' => 'Ürün Galerileri',
        'routes' => [
            'list' => route('admin.galleries.list'),
            'index' => route('admin.products.galleries.index', $product),
            'attach' => route('admin.products.galleries.attach', $product),
            'detach' => route('admin.products.galleries.detach', $product),
            'reorder' => route('admin.products.galleries.reorder', $product),
        ],
        'slots' => [
            'main' => 'Ana Alan',
            'sidebar' => 'Yan Alan',
        ],
    ])
@endif
