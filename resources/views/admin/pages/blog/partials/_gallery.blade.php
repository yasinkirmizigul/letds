@if(empty($blogPost) || empty($blogPost->id))
    <div class="kt-card">
        <div class="kt-card-content p-5">
            <div class="rounded-2xl border border-dashed border-border bg-background/75 px-4 py-4 text-sm text-muted-foreground">
                Galeri eklemek için yazıyı önce kaydedin. Kayıttan sonra galerileri ana alan ve yan alan olarak bağlayabilirsiniz.
            </div>
        </div>
    </div>
@else
    @include('admin.components.gallery-manager', [
        'id' => 'blog-' . $blogPost->id,
        'title' => 'Yazı Galerileri',
        'routes' => [
            'list' => route('admin.galleries.list'),
            'index' => route('admin.blog.galleries.index', $blogPost),
            'attach' => route('admin.blog.galleries.attach', $blogPost),
            'detach' => route('admin.blog.galleries.detach', $blogPost),
            'reorder' => route('admin.blog.galleries.reorder', $blogPost),
        ],
        'slots' => [
            'main' => 'Ana Alan',
            'sidebar' => 'Yan Alan',
        ],
    ])
@endif
