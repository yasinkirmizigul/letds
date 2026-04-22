<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Controller;
use App\Models\Admin\Gallery\Gallery;
use App\Support\Audit\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    public function index()
    {
        return view('admin.pages.galleries.index', [
            'pageTitle' => 'Galeriler',
            'mode' => 'active',
            'stats' => $this->stats(),
        ]);
    }

    public function trash()
    {
        return view('admin.pages.galleries.index', [
            'pageTitle' => 'Galeri Silinen',
            'mode' => 'trash',
            'stats' => $this->stats(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $mode = $request->string('mode', 'active')->toString();

        $query = $mode === 'trash'
            ? Gallery::onlyTrashed()->latest('id')
            : Gallery::query()->latest('id');

        if ($term = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        $query
            ->withCount('items')
            ->select('galleries.*')
            ->selectSub(
                DB::table('galleryables')
                    ->selectRaw('count(*)')
                    ->whereColumn('gallery_id', 'galleries.id'),
                'attached_count'
            );

        $perPage = max(1, min(96, (int) $request->input('perpage', 24)));
        $items = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $items->getCollection()->map(fn (Gallery $gallery) => [
                'id' => $gallery->id,
                'name' => $gallery->name,
                'slug' => $gallery->slug,
                'description' => $gallery->description,
                'items_count' => (int) ($gallery->items_count ?? 0),
                'attached_count' => (int) ($gallery->attached_count ?? 0),
                'deleted_at' => $gallery->deleted_at?->toDateTimeString(),
                'updated_at' => $gallery->updated_at?->toDateTimeString(),
                'edit_url' => route('admin.galleries.edit', $gallery),
                'delete_url' => route('admin.galleries.destroy', $gallery),
            ])->values(),
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
        return view('admin.pages.galleries.create', [
            'pageTitle' => 'Galeri Oluştur',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:galleries,slug'],
            'description' => ['nullable', 'string'],
        ]);

        $data['slug'] = $data['slug'] ? Str::slug($data['slug']) : Str::slug($data['name']);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $gallery = Gallery::create($data);

        AuditEvent::log('gallery.create', ['gallery_id' => $gallery->id]);

        return redirect()
            ->route('admin.galleries.edit', ['gallery' => $gallery->id])
            ->with('success', 'Galeri oluşturuldu.');
    }

    public function edit(Gallery $gallery)
    {
        return view('admin.pages.galleries.edit', [
            'pageTitle' => 'Galeri Düzenle',
            'gallery' => $gallery,
        ]);
    }

    public function update(Request $request, Gallery $gallery)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:galleries,slug,' . $gallery->id],
            'description' => ['nullable', 'string'],
        ]);

        $data['slug'] = $data['slug'] ? Str::slug($data['slug']) : Str::slug($data['name']);
        $data['updated_by'] = auth()->id();

        $gallery->update($data);

        AuditEvent::log('gallery.update', ['gallery_id' => $gallery->id]);

        return back()->with('success', 'Galeri güncellendi.');
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->delete();

        AuditEvent::log('gallery.delete', ['gallery_id' => $gallery->id]);

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Galeri çöp kutusuna taşındı.',
            ]);
        }

        return back()->with('success', 'Galeri çöp kutusuna taşındı.');
    }

    public function restore(int $id)
    {
        $gallery = Gallery::onlyTrashed()->findOrFail($id);
        $gallery->restore();

        AuditEvent::log('gallery.restore', ['gallery_id' => $gallery->id]);

        return back()->with('success', 'Galeri geri yüklendi.');
    }

    public function forceDestroy(int $id)
    {
        $gallery = Gallery::onlyTrashed()->findOrFail($id);

        $attachedCount = DB::table('galleryables')
            ->where('gallery_id', $gallery->id)
            ->count();

        if ($attachedCount > 0) {
            return back()->with('error', 'Bu galeri içeriklere bağlı. Önce bağlantıları kaldır.');
        }

        $gallery->forceDelete();
        AuditEvent::log('gallery.force_delete', ['gallery_id' => $id]);

        return back()->with('success', 'Galeri kalıcı silindi.');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = array_values(array_filter((array) $request->input('ids', [])));
        if (!$ids) return response()->json(['ok' => true]);

        Gallery::whereIn('id', $ids)->delete();

        AuditEvent::log('gallery.bulk_delete', ['ids' => $ids]);

        return response()->json(['ok' => true]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = array_values(array_filter((array) $request->input('ids', [])));
        if (!$ids) return response()->json(['ok' => true]);

        Gallery::onlyTrashed()->whereIn('id', $ids)->restore();

        AuditEvent::log('gallery.bulk_restore', ['ids' => $ids]);

        return response()->json(['ok' => true]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = array_values(array_filter((array) $request->input('ids', [])));
        if (!$ids) return response()->json(['ok' => true]);

        $blocked = DB::table('galleryables')
            ->whereIn('gallery_id', $ids)
            ->pluck('gallery_id')
            ->unique()
            ->values()
            ->all();

        $allowed = array_values(array_diff($ids, $blocked));

        if ($allowed) {
            Gallery::onlyTrashed()->whereIn('id', $allowed)->forceDelete();
        }

        AuditEvent::log('gallery.bulk_force_delete', [
            'allowed' => $allowed,
            'blocked' => $blocked,
        ]);

        return response()->json(['ok' => true, 'blocked' => $blocked]);
    }

    private function stats(): array
    {
        return [
            'active' => Gallery::query()->count(),
            'trash' => Gallery::onlyTrashed()->count(),
            'items' => DB::table('gallery_items')->count(),
            'attached' => DB::table('galleryables')->count(),
        ];
    }
}
