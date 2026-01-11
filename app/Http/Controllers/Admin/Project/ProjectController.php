<?php

namespace App\Http\Controllers\Admin\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Project\ProjectStoreRequest;
use App\Http\Requests\Admin\Project\ProjectUpdateRequest;
use App\Models\Admin\Category;
use App\Models\Admin\Project\Project;
use App\Support\Audit\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->string('mode', 'active')->toString(); // active|trash
        $isTrash = $mode === 'trash';

        $q = $request->string('q')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        $query = $isTrash ? Project::onlyTrashed() : Project::query();

        $items = $query
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.pages.projects.index', [
            'mode' => $isTrash ? 'trash' : 'active',
            'projects' => $items,
            'q' => $q,
            'perPage' => $perPage,
            'pageTitle' => 'Projeler',
        ]);
    }

    public function trash(Request $request)
    {
        $request->merge(['mode' => 'trash']);
        return $this->index($request);
    }

    /**
     * Opsiyonel JSON list endpoint (mode destekli)
     * /admin/projects/list?mode=trash&q=...&perpage=25&page=1
     */
    public function list(Request $request): JsonResponse
    {
        $mode = $request->string('mode', 'active')->toString();
        $q = $request->string('q')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        $query = $mode === 'trash'
            ? Project::onlyTrashed()->latest('id')
            : Project::query()->latest('id');

        $items = $query
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function create()
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $categoryOptions = $this->categoryOptions($categories);

        return view('admin.pages.projects.create', [
            'categories' => $categories,
            'categoryOptions' => $categoryOptions,
            'selectedCategoryIds' => [],
            'pageTitle' => 'Proje Ekle',
        ]);
    }

    public function store(ProjectStoreRequest $request)
    {
        $data = $request->validated();

        $slug = $data['slug'] ?: Str::slug($data['title']);
        $data['slug'] = $this->uniqueSlug($slug);

        $featuredMediaId = $data['featured_media_id'] ?? null;
        unset($data['featured_media_id']);

        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $project = Project::create($data);

        // categories
        if (method_exists($project, 'categories')) {
            $project->categories()->sync($categoryIds);
        }

        // featured media (mediables: collection=featured)
        if ($featuredMediaId) {
            $this->syncFeaturedMedia($project, (int) $featuredMediaId);
        }

        AuditEvent::log('projects.create', [
            'project_id' => (int) $project->id,
            'title' => (string) $project->title,
        ]);

        return redirect()
            ->route('admin.projects.index')
            ->with('success', 'Proje oluşturuldu.');
    }

    public function edit(Project $project)
    {
        try {
            $project->load(['categories' => fn ($q) => $q->withTrashed()]);
        } catch (\Throwable $e) {
            $project->load('categories');
        }

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $categoryOptions = $this->categoryOptions($categories);

        $selectedCategoryIds = $project->categories
            ? $project->categories->pluck('id')->map(fn ($v) => (int) $v)->values()->all()
            : [];

        $featuredMediaId = (int) (DB::table('mediables')
            ->where('mediable_type', Project::class)
            ->where('mediable_id', $project->id)
            ->where('collection', 'featured')
            ->orderBy('order')
            ->value('media_id') ?? 0);

        return view('admin.pages.projects.edit', [
            'project' => $project,
            'categories' => $categories,
            'categoryOptions' => $categoryOptions,
            'selectedCategoryIds' => $selectedCategoryIds,
            'featuredMediaId' => $featuredMediaId ?: null,
            'pageTitle' => 'Proje Düzenle',
        ]);
    }

    public function update(ProjectUpdateRequest $request, Project $project)
    {
        $data = $request->validated();

        $slug = $data['slug'] ?: Str::slug($data['title']);
        $data['slug'] = $this->uniqueSlug($slug, $project->id);

        $featuredMediaId = $data['featured_media_id'] ?? null;
        unset($data['featured_media_id']);

        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $oldStatus = (string) ($project->status ?? 'draft');

        $project->update($data);

        if (method_exists($project, 'categories')) {
            $project->categories()->sync($categoryIds);
        }

        // featured media
        $this->syncFeaturedMedia($project, $featuredMediaId ? (int) $featuredMediaId : null);

        AuditEvent::log('projects.update', [
            'project_id' => (int) $project->id,
        ]);

        // status değişimi update içinden de gelmiş olabilir
        if (($data['status'] ?? null) && $oldStatus !== $data['status']) {
            AuditEvent::log('projects.state.change', [
                'project_id' => (int) $project->id,
                'from' => $oldStatus,
                'to' => (string) $data['status'],
            ]);
        }

        return redirect()
            ->route('admin.projects.edit', $project)
            ->with('success', 'Proje güncellendi.');
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        AuditEvent::log('projects.delete', [
            'project_id' => (int) $project->id,
        ]);

        return response()->json(['ok' => true]);
    }

    public function restore(int $id): JsonResponse
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        $project->restore();

        AuditEvent::log('projects.restore', [
            'project_id' => (int) $project->id,
        ]);

        return response()->json(['ok' => true, 'data' => ['restored' => true]]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $project = Project::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($project) {
            // categories pivot temizliği
            if (method_exists($project, 'categories')) {
                $project->categories()->detach();
            }

            // featured mediable temizliği
            DB::table('mediables')
                ->where('mediable_type', Project::class)
                ->where('mediable_id', $project->id)
                ->delete();

            // galleryables temizliği
            DB::table('galleryables')
                ->where('galleryable_type', Project::class)
                ->where('galleryable_id', $project->id)
                ->delete();

            $project->forceDelete();
        });

        AuditEvent::log('projects.force_delete', [
            'project_id' => (int) $project->id,
        ]);

        return response()->json(['ok' => true, 'data' => ['force_deleted' => true]]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $count = Project::query()->whereIn('id', $ids)->delete();

        AuditEvent::log('projects.bulk.delete', [
            'ids' => $ids,
            'deleted' => (int) $count,
        ]);

        return response()->json(['ok' => true, 'data' => ['deleted' => $count]]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $count = Project::onlyTrashed()->whereIn('id', $ids)->restore();

        AuditEvent::log('projects.bulk.restore', [
            'ids' => $ids,
            'restored' => (int) $count,
        ]);

        return response()->json(['ok' => true, 'data' => ['restored' => $count]]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $projects = Project::withTrashed()->whereIn('id', $ids)->get();

        DB::transaction(function () use ($projects) {
            foreach ($projects as $p) {
                if (method_exists($p, 'categories')) {
                    $p->categories()->detach();
                }

                DB::table('mediables')
                    ->where('mediable_type', Project::class)
                    ->where('mediable_id', $p->id)
                    ->delete();

                DB::table('galleryables')
                    ->where('galleryable_type', Project::class)
                    ->where('galleryable_id', $p->id)
                    ->delete();

                $p->forceDelete();
            }
        });

        AuditEvent::log('projects.bulk.force_delete', [
            'ids' => $ids,
            'force_deleted' => (int) $projects->count(),
        ]);

        return response()->json(['ok' => true, 'data' => ['force_deleted' => $projects->count()]]);
    }

    public function changeStatus(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in(['draft', 'active', 'archived'])],
        ]);

        $from = (string) ($project->status ?? 'draft');
        $to = (string) $data['status'];

        if ($from !== $to) {
            $project->status = $to;
            $project->save();

            AuditEvent::log('projects.state.change', [
                'project_id' => (int) $project->id,
                'from' => $from,
                'to' => $to,
            ]);
        }

        return response()->json(['ok' => true, 'data' => ['status' => $project->status]]);
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug);
        $candidate = $base;
        $i = 2;

        while (
        Project::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()
        ) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    private function categoryOptions($categories): array
    {
        $byParent = [];
        foreach ($categories as $c) {
            $pid = (int) ($c->parent_id ?? 0);
            $byParent[$pid][] = $c;
        }

        $out = [];
        $walk = function ($parentId, $depth) use (&$walk, &$out, $byParent) {
            $list = $byParent[(int) $parentId] ?? [];
            foreach ($list as $c) {
                $prefix = str_repeat('— ', $depth);
                $out[] = [
                    'id' => (int) $c->id,
                    'label' => $prefix . $c->name,
                ];
                $walk((int) $c->id, $depth + 1);
            }
        };

        $walk(0, 0);
        return $out;
    }

    private function syncFeaturedMedia(Project $project, ?int $mediaId): void
    {
        // mevcut featured temizle
        DB::table('mediables')
            ->where('mediable_type', Project::class)
            ->where('mediable_id', $project->id)
            ->where('collection', 'featured')
            ->delete();

        if (!$mediaId) {
            AuditEvent::log('projects.featured.detach', [
                'project_id' => (int) $project->id,
            ]);
            return;
        }

        DB::table('mediables')->insert([
            'media_id' => $mediaId,
            'mediable_type' => Project::class,
            'mediable_id' => $project->id,
            'collection' => 'featured',
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditEvent::log('projects.featured.attach', [
            'project_id' => (int) $project->id,
            'media_id' => (int) $mediaId,
        ]);
    }
    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        Project::query()
            ->whereIn('id', $data['ids'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function bulkForceDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $data['ids'];

        // 1) Bu projelerin mediables featured/gallery gibi pivotlarını temizle (varsa)
        DB::table('mediables')
            ->where('mediable_type', Project::class)
            ->whereIn('mediable_id', $ids)
            ->delete();

        // 2) Kalıcı sil
        Project::onlyTrashed()
            ->whereIn('id', $ids)
            ->forceDelete();

        return response()->json(['ok' => true]);
    }

}
