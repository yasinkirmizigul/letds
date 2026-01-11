{{-- Project Galleries (shared component) --}}
@include('admin.components.gallery-manager', [
    'id' => 'project-' . $project->id,
    'title' => 'Galeriler',
    'routes' => [
        'list' => route('admin.galleries.list'),
        'index' => route('admin.projects.galleries.index', $project),
        'attach' => route('admin.projects.galleries.attach', $project),
        'detach' => route('admin.projects.galleries.detach', $project),
        'reorder' => route('admin.projects.galleries.reorder', $project),
    ],
    'slots' => [
        'main' => 'Ana',
        'sidebar' => 'Sidebar',
    ],
])
