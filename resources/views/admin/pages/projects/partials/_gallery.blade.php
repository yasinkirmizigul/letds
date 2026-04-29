@if(empty($project) || empty($project->id))
    <div class="kt-card">
        <div class="kt-card-content p-5">
            <div class="rounded-2xl border border-dashed border-border bg-background/75 px-4 py-4 text-sm text-muted-foreground">
                Galeri eklemek için projeyi önce kaydedin. Kayıttan sonra galerileri ana alan ve yan alan olarak bağlayabilirsiniz.
            </div>
        </div>
    </div>
@else
    @include('admin.components.gallery-manager', [
        'id' => 'project-' . $project->id,
        'title' => 'Proje Galerileri',
        'routes' => [
            'list' => route('admin.galleries.list'),
            'index' => route('admin.projects.galleries.index', $project),
            'attach' => route('admin.projects.galleries.attach', $project),
            'detach' => route('admin.projects.galleries.detach', $project),
            'reorder' => route('admin.projects.galleries.reorder', $project),
        ],
        'slots' => [
            'main' => 'Ana Alan',
            'sidebar' => 'Yan Alan',
        ],
    ])
@endif
