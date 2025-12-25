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
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $q = Media::query()->latest('id');

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
                'file'      => ['required_without:files', 'file', 'max:20480'], // 20MB
                'files'     => ['required_without:file', 'array'],
                'files.*'   => ['file', 'max:20480'], // 20MB each
                'title'     => ['nullable', 'string', 'max:255'],
                'alt'       => ['nullable', 'string', 'max:255'],
            ]);

            $title = $request->input('title');
            $alt   = $request->input('alt');

            // Multi
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

                return response()->json([
                    'ok'   => true,
                    'data' => $uploaded,
                ]);
            }

            // Single
            $file = $request->file('file');
            $m = $this->mediaService->store($file, [
                'title' => $title,
                'alt'   => $alt,
            ]);

            return response()->json([
                'ok'   => true,
                'data' => $this->mediaPayload($m),
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'ok'    => false,
                'error' => ['message' => $e->getMessage()],
            ], 422);
        }
    }

    // ✅ TOPLU SİLME
    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || count($ids) === 0) {
            return response()->json([
                'ok'    => false,
                'error' => ['message' => 'Seçili kayıt yok.'],
            ], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $items = Media::query()->whereIn('id', $ids)->get();

        foreach ($items as $m) {
            $this->mediaService->delete($m);
        }

        return response()->json([
            'ok'   => true,
            'data' => ['deleted' => $items->count()],
        ]);
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->mediaService->delete($media);

        return response()->json([
            'ok'   => true,
            'data' => ['deleted' => true],
        ]);
    }

    private function mediaPayload(Media $m): array
    {
        return [
            'id'            => $m->id,
            'uuid'          => $m->uuid,
            'url'           => $m->url(),
            'thumb_url'     => $m->thumbUrl(), // ✅ eklendi
            'original_name' => $m->original_name,
            'mime_type'     => $m->mime_type,
            'size'          => (int) $m->size,
            'width'         => $m->width,
            'height'        => $m->height,
            'is_image'      => $m->isImage(),
            'created_at'    => $m->created_at?->toDateTimeString(),
        ];
    }
}
