<?php

namespace App\Http\Controllers\Admin\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Project\ProjectStoreRequest;
use App\Http\Requests\Admin\Project\ProjectUpdateRequest;
use App\Models\Admin\Category;
use App\Models\Admin\Project\Project;
use App\Support\Audit\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $mode = $request->string('mode', 'active')->toString();
        $isTrash = $mode === 'trash';

        $q = trim($request->string('q')->toString());
        $status = $request->string('status', 'all')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        $selectedCategoryIds = collect($request->input('category_ids', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $projects = ($isTrash ? Project::onlyTrashed() : Project::query())
            ->with([
                'categories:id,name',
                'featuredMedia',
            ])
            ->search($q)
            ->inStatus($status)
            ->when(!empty($selectedCategoryIds), function ($builder) use ($selectedCategoryIds) {
                $builder->whereHas('categories', function ($categoryQuery) use ($selectedCategoryIds) {
                    $categoryQuery->whereIn('categories.id', $selectedCategoryIds);
                });
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.pages.projects.index', [
            'mode' => $isTrash ? 'trash' : 'active',
            'projects' => $projects,
            'q' => $q,
            'status' => $status,
            'perPage' => $perPage,
            'categoryOptions' => $this->categoryOptions($categories),
            'selectedCategoryIds' => $selectedCategoryIds,
            'statusOptions' => Project::statusOptionsSorted(),
            'publicStatuses' => Project::PUBLIC_STATUSES,
            'stats' => [
                'all' => Project::query()->count(),
                'featured' => Project::query()->featured()->count(),
                'public' => Project::query()->publicVisible()->count(),
                'workflow' => Project::query()
                    ->whereIn('status', [
                        Project::STATUS_APPOINTMENT_PENDING,
                        Project::STATUS_APPOINTMENT_SCHEDULED,
                        Project::STATUS_APPOINTMENT_DONE,
                        Project::STATUS_DEV_PENDING,
                        Project::STATUS_DEV_IN_PROGRESS,
                    ])
                    ->count(),
                'trash' => Project::onlyTrashed()->count(),
            ],
            'pageTitle' => 'Projeler',
        ]);
    }

    public function trash(Request $request): View
    {
        $request->merge(['mode' => 'trash']);

        return $this->index($request);
    }

    public function list(Request $request): JsonResponse
    {
        $mode = $request->string('mode', 'active')->toString();
        $q = trim($request->string('q')->toString());
        $status = $request->string('status', 'all')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        $selectedCategoryIds = collect($request->input('category_ids', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $items = ($mode === 'trash' ? Project::onlyTrashed() : Project::query())
            ->with(['categories:id,name', 'featuredMedia'])
            ->search($q)
            ->inStatus($status)
            ->when(!empty($selectedCategoryIds), function ($builder) use ($selectedCategoryIds) {
                $builder->whereHas('categories', function ($categoryQuery) use ($selectedCategoryIds) {
                    $categoryQuery->whereIn('categories.id', $selectedCategoryIds);
                });
            })
            ->latest('id')
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

    public function create(): View
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return view('admin.pages.projects.create', [
            'categoryOptions' => $this->categoryOptions($categories),
            'selectedCategoryIds' => [],
            'statusOptions' => Project::statusOptionsSorted(),
            'publicStatuses' => Project::PUBLIC_STATUSES,
            'pageTitle' => 'Proje Ekle',
        ]);
    }

    public function store(ProjectStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $project = DB::transaction(function () use ($validated) {
            $project = Project::create($this->buildPersistenceData($validated));
            $this->syncCategories($project, $validated['category_ids'] ?? []);

            return $project;
        });

        $this->syncFeaturedAsset(
            $project,
            $request,
            isset($validated['featured_media_id']) ? (int) $validated['featured_media_id'] : null,
            (bool) ($validated['clear_featured_image'] ?? false)
        );

        AuditEvent::log('projects.create', [
            'project_id' => (int) $project->id,
            'title' => (string) $project->title,
        ]);

        return redirect()
            ->route('admin.projects.index')
            ->with('success', 'Proje oluşturuldu.');
    }

    public function edit(Project $project): View
    {
        $project->load([
            'categories:id,name,parent_id',
            'featuredMedia',
        ]);

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return view('admin.pages.projects.edit', [
            'project' => $project,
            'categoryOptions' => $this->categoryOptions($categories),
            'selectedCategoryIds' => $project->categories
                ->pluck('id')
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all(),
            'featuredMediaId' => $project->featuredMediaOne()?->id,
            'statusOptions' => Project::statusOptionsSorted(),
            'publicStatuses' => Project::PUBLIC_STATUSES,
            'pageTitle' => 'Proje Düzenle',
        ]);
    }

    public function update(ProjectUpdateRequest $request, Project $project): RedirectResponse
    {
        $validated = $request->validated();
        $oldStatus = (string) ($project->status ?? Project::STATUS_DRAFT);

        DB::transaction(function () use ($validated, &$project) {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            $project->update($this->buildPersistenceData($validated, $project));
            $this->syncCategories($project, $validated['category_ids'] ?? []);
        });

        $this->syncFeaturedAsset(
            $project,
            $request,
            isset($validated['featured_media_id']) ? (int) $validated['featured_media_id'] : null,
            (bool) ($validated['clear_featured_image'] ?? false)
        );

        AuditEvent::log('projects.update', [
            'project_id' => (int) $project->id,
        ]);

        if ($oldStatus !== ($validated['status'] ?? $oldStatus)) {
            AuditEvent::log('projects.workflow.change', [
                'project_id' => (int) $project->id,
                'from' => $oldStatus,
                'to' => (string) $validated['status'],
            ]);
        }

        return redirect()
            ->route('admin.projects.edit', $project)
            ->with('success', 'Proje güncellendi.');
    }

    public function destroy(Request $request, Project $project)
    {
        $project->delete();

        AuditEvent::log('projects.delete', [
            'project_id' => (int) $project->id,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Proje çöp kutusuna taşındı.',
            ]);
        }

        return redirect()
            ->route('admin.projects.index')
            ->with('success', 'Proje çöp kutusuna taşındı.');
    }

    public function restore(int $id): JsonResponse
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        $project->restore();

        AuditEvent::log('projects.restore', [
            'project_id' => (int) $project->id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Proje geri yüklendi.',
            'data' => ['restored' => true],
        ]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $project = Project::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($project) {
            $this->syncCategories($project, []);
            $this->deleteLegacyFeaturedImage($project);
            $this->syncFeaturedMedia($project, null);

            DB::table('galleryables')
                ->where('galleryable_type', Project::class)
                ->where('galleryable_id', $project->id)
                ->delete();

            $project->forceDelete();
        });

        AuditEvent::log('projects.force_delete', [
            'project_id' => (int) $project->id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Proje kalıcı olarak silindi.',
            'data' => ['force_deleted' => true],
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedBulkIds($request);
        $count = Project::query()->whereIn('id', $ids)->delete();

        AuditEvent::log('projects.bulk.delete', [
            'ids' => $ids,
            'deleted' => (int) $count,
        ]);

        return response()->json([
            'ok' => true,
            'message' => $count . ' proje çöp kutusuna taşındı.',
            'data' => ['deleted' => $count],
        ]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $this->validatedBulkIds($request);
        $count = Project::onlyTrashed()->whereIn('id', $ids)->restore();

        AuditEvent::log('projects.bulk.restore', [
            'ids' => $ids,
            'restored' => (int) $count,
        ]);

        return response()->json([
            'ok' => true,
            'message' => $count . ' proje geri yüklendi.',
            'data' => ['restored' => $count],
        ]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedBulkIds($request);
        $projects = Project::withTrashed()->whereIn('id', $ids)->get();

        DB::transaction(function () use ($projects) {
            foreach ($projects as $project) {
                $this->syncCategories($project, []);
                $this->deleteLegacyFeaturedImage($project);
                $this->syncFeaturedMedia($project, null);

                DB::table('galleryables')
                    ->where('galleryable_type', Project::class)
                    ->where('galleryable_id', $project->id)
                    ->delete();

                $project->forceDelete();
            }
        });

        AuditEvent::log('projects.bulk.force_delete', [
            'ids' => $ids,
            'force_deleted' => (int) $projects->count(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => $projects->count() . ' proje kalıcı olarak silindi.',
            'data' => ['force_deleted' => $projects->count()],
        ]);
    }

    public function checkSlug(Request $request): JsonResponse
    {
        $rawSlug = trim((string) $request->query('slug', ''));
        $ignoreId = $request->integer('ignore');
        $normalizedSlug = Str::slug($rawSlug);

        if ($normalizedSlug === '') {
            return response()->json([
                'ok' => false,
                'available' => false,
                'message' => 'Slug boş olamaz.',
            ]);
        }

        $suggested = $this->uniqueSlug($normalizedSlug, $ignoreId);
        $isAvailable = $suggested === $normalizedSlug;

        return response()->json([
            'ok' => true,
            'available' => $isAvailable,
            'normalized' => $normalizedSlug,
            'suggested' => $suggested,
            'message' => $isAvailable ? 'Slug uygun.' : 'Bu slug kullanılıyor. Onerilen slug hazırlandı.',
        ]);
    }

    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(Project::STATUS_OPTIONS))],
        ]);

        $from = (string) ($project->status ?? Project::STATUS_DRAFT);
        $to = (string) $payload['status'];

        if ($from !== $to) {
            $project->status = $to;
            $project->save();

            AuditEvent::log('projects.workflow.change', [
                'project_id' => (int) $project->id,
                'from' => $from,
                'to' => $to,
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Proje durumu güncellendi.',
            'data' => [
                'id' => $project->id,
                'status' => $project->status,
                'status_label' => Project::statusLabel($project->status),
                'status_badge' => Project::statusBadgeClass($project->status),
                'public_visible' => Project::statusIsPublic($project->status),
            ],
        ]);
    }

    public function toggleFeatured(Request $request, Project $project): JsonResponse
    {
        $payload = $request->validate([
            'is_featured' => ['required', 'boolean'],
        ]);

        return DB::transaction(function () use ($project, $payload) {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            $want = (bool) $payload['is_featured'];
            $wasFeatured = (bool) $project->is_featured;

            if ($want) {
                $this->guardFeaturedLimit($project->id);
                $project->is_featured = true;
                $project->featured_at = $project->featured_at ?? now();
            } else {
                $project->is_featured = false;
                $project->featured_at = null;
            }

            $project->save();

            if ($wasFeatured !== (bool) $project->is_featured) {
                AuditEvent::log('projects.featured.toggle', [
                    'project_id' => (int) $project->id,
                    'is_featured' => (bool) $project->is_featured,
                ]);
            }

            return response()->json([
                'ok' => true,
                'message' => $project->is_featured ? 'Proje anasayfaya alındı.' : 'Proje anasayfadan kaldırıldı.',
                'data' => [
                    'id' => $project->id,
                    'is_featured' => (bool) $project->is_featured,
                    'featured_at' => $project->featured_at?->format('d.m.Y H:i'),
                ],
            ]);
        });
    }

    private function buildPersistenceData(array $validated, ?Project $project = null): array
    {
        $slugSource = $validated['slug'] ?: $validated['title'];

        $data = [
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($slugSource, $project?->id),
            'content' => $validated['content'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'meta_keywords' => $validated['meta_keywords'] ?? null,
            'status' => $validated['status'] ?? Project::STATUS_APPOINTMENT_PENDING,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'featured_at' => $this->resolveFeaturedAt((bool) ($validated['is_featured'] ?? false), $project),
            'appointment_id' => $validated['appointment_id'] ?? null,
        ];

        if ($data['is_featured']) {
            $this->guardFeaturedLimit($project?->id);
        }

        return $data;
    }

    private function syncCategories(Project $project, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $project->categories()->sync($ids);
    }

    private function syncFeaturedAsset(
        Project $project,
        Request $request,
        ?int $featuredMediaId,
        bool $clearFeaturedImage
    ): void {
        if ($request->hasFile('featured_image')) {
            $this->deleteLegacyFeaturedImage($project);

            $path = $request->file('featured_image')->store('projects/featured', 'public');
            $project->forceFill(['featured_image_path' => $path])->save();

            $this->syncFeaturedMedia($project, null);

            AuditEvent::log('projects.featured.upload', [
                'project_id' => (int) $project->id,
                'path' => $path,
            ]);

            return;
        }

        if ($clearFeaturedImage && !$featuredMediaId) {
            $this->deleteLegacyFeaturedImage($project);
            $this->syncFeaturedMedia($project, null);

            return;
        }

        $this->syncFeaturedMedia($project, $featuredMediaId);
    }

    private function deleteLegacyFeaturedImage(Project $project): void
    {
        if (!$project->featured_image_path) {
            return;
        }

        Storage::disk('public')->delete($project->featured_image_path);
        $project->forceFill(['featured_image_path' => null])->save();
    }

    private function syncFeaturedMedia(Project $project, ?int $mediaId): void
    {
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

    private function resolveFeaturedAt(bool $isFeatured, ?Project $project = null)
    {
        if (!$isFeatured) {
            return null;
        }

        return $project?->featured_at ?? now();
    }

    private function guardFeaturedLimit(?int $exceptId = null): void
    {
        $query = Project::query()
            ->where('is_featured', true)
            ->lockForUpdate();

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->count() >= 5) {
            throw ValidationException::withMessages([
                'is_featured' => 'En fazla 5 proje aynı anda anasayfada gösterilebilir.',
            ]);
        }
    }

    private function validatedBulkIds(Request $request): array
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || count($ids) === 0) {
            throw ValidationException::withMessages([
                'ids' => 'Seçili kayıt yok.',
            ]);
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'project';
        $candidate = $base;
        $suffix = 2;

        while (
            Project::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function categoryOptions($categories): array
    {
        $byParent = [];

        foreach ($categories as $category) {
            $parentId = (int) ($category->parent_id ?? 0);
            $byParent[$parentId][] = $category;
        }

        $options = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$options, $byParent) {
            foreach ($byParent[$parentId] ?? [] as $category) {
                $options[] = [
                    'id' => (int) $category->id,
                    'label' => str_repeat('-- ', $depth) . $category->name,
                ];

                $walk((int) $category->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $options;
    }
}
