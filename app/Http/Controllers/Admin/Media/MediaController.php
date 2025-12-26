<?php

namespace App\Http\Controllers\Admin\Media;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use App\Services\Admin\Media\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function index()
    {
        return view('admin.pages.media.index', [
            'pageTitle' => 'Medya Kütüphanesi',
            'mode' => 'active',
        ]);
    }

    public function trash()
    {
        return view('admin.pages.media.index', [
            'pageTitle' => 'Silinen Medyalar',
            'mode' => 'trash',
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $mode = $request->string('mode', 'active')->toString();

        $q = $mode === 'trash'
            ? Media::onlyTrashed()->latest('id')
            : Media::query()->latest('id');

        if ($type = $request->string('type')->toString()) {
            if ($type === 'image') {
                $q->where('mime_type', 'like', 'image/%');
            } elseif ($type === 'video') {
                $q->where('mime_type', 'like', 'video/%');
            } elseif ($type === 'pdf') {
                $q->where('mime_type', 'application/pdf');
            }
        }

        if ($term = $request->string('q')->toString()) {
            $q->where(function ($qq) use ($term) {
                $qq->where('original_name', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('alt', 'like', "%{$term}%");
            });
        }

        $perPage = max(1, min(96, (int) $request->input('perpage', 24)));
        $items = $q->paginate($perPage);

        return response()->json([
            'ok'   => true,
            'data' => $items->getCollection()->map(fn (Media $m) => $this->mediaPayload($m))->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file'      => ['required_without:files', 'file', 'max:20480'],
                'files'     => ['required_without:file', 'array'],
                'files.*'   => ['file', 'max:20480'],
                'title'     => ['nullable', 'string', 'max:255'],
                'alt'       => ['nullable', 'string', 'max:255'],
            ]);

            $title = $request->input('title');
            $alt   = $request->input('alt');

            $files = $request->file('files');
            if (is_array($files) && count($files)) {
                $uploaded = [];
                foreach ($files as $f) {
                    $m = $this->mediaService->store($f, [
                        'title' => $title,
                        'alt'   => $alt,
                    ]);
                    $uploaded[] = $this->mediaPayload($m);
                }

                return response()->json(['ok' => true, 'data' => $uploaded]);
            }

            $file = $request->file('file');
            $m = $this->mediaService->store($file, [
                'title' => $title,
                'alt'   => $alt,
            ]);

            return response()->json(['ok' => true, 'data' => $this->mediaPayload($m)]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'ok'    => false,
                'error' => ['message' => $e->getMessage()],
            ], 422);
        }
    }

    // Single soft delete
    public function destroy(Media $media): JsonResponse
    {
        $media->delete();

        return response()->json([
            'ok'   => true,
            'data' => ['deleted' => true],
        ]);
    }

    // Single restore
    public function restore(int $id): JsonResponse
    {
        $m = Media::onlyTrashed()->findOrFail($id);
        $m->restore();

        return response()->json([
            'ok'   => true,
            'data' => ['restored' => true],
        ]);
    }

    // Single force delete (db + file)
    public function forceDestroy(int $id): JsonResponse
    {
        $m = Media::withTrashed()->findOrFail($id);
        $m->forceDelete();

        return response()->json([
            'ok'   => true,
            'data' => ['force_deleted' => true],
        ]);
    }

    // Bulk soft delete
    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $count = Media::query()->whereIn('id', $ids)->delete(); // soft delete

        return response()->json([
            'ok'   => true,
            'data' => ['deleted' => $count],
        ]);
    }

    // Bulk restore
    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $count = Media::onlyTrashed()->whereIn('id', $ids)->restore();

        return response()->json([
            'ok'   => true,
            'data' => ['restored' => $count],
        ]);
    }

    // Bulk force delete
    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $items = Media::withTrashed()->whereIn('id', $ids)->get();

        foreach ($items as $m) {
            $m->forceDelete();
        }

        return response()->json([
            'ok'   => true,
            'data' => ['force_deleted' => $items->count()],
        ]);
    }

    private function mediaPayload(Media $m): array
    {
        return [
            'id'            => $m->id,
            'uuid'          => $m->uuid,
            'url'           => $m->url(),
            'thumb_url'     => $m->thumbUrl(),
            'original_name' => $m->original_name,
            'mime_type'     => $m->mime_type,
            'size'          => (int) $m->size,
            'width'         => $m->width,
            'height'        => $m->height,
            'is_image'      => $m->isImage(),
            'created_at'    => $m->created_at?->toDateTimeString(),
            'deleted_at'    => $m->deleted_at?->toDateTimeString(),
        ];
    }
}
