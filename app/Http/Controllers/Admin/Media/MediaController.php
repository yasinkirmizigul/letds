<?php

namespace App\Http\Controllers\Admin\Media;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use App\Services\Admin\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function index()
    {
        return view('admin.pages.media.index', [
            'pageTitle' => 'Medya Kütüphanesi',
            'mode' => 'active',
            'stats' => $this->stats(),
        ]);
    }

    public function trash()
    {
        return view('admin.pages.media.index', [
            'pageTitle' => 'Silinen Medyalar',
            'mode' => 'trash',
            'stats' => $this->stats(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $mode = $request->string('mode', 'active')->toString();

        $query = $mode === 'trash'
            ? Media::onlyTrashed()->latest('id')
            : Media::query()->latest('id');

        if ($type = $request->string('type')->toString()) {
            if ($type === 'image') {
                $query->where('mime_type', 'like', 'image/%');
            } elseif ($type === 'video') {
                $query->where('mime_type', 'like', 'video/%');
            } elseif ($type === 'pdf') {
                $query->where('mime_type', 'application/pdf');
            }
        }

        if ($term = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($term) {
                $builder->where('original_name', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('alt', 'like', "%{$term}%");
            });
        }

        $perPage = max(1, min(96, (int) $request->input('perpage', 24)));
        $items = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $items->getCollection()->map(fn (Media $media) => $this->mediaPayload($media))->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => ['required_without:files', 'file', 'max:20480'],
                'files' => ['required_without:file', 'array'],
                'files.*' => ['file', 'max:20480'],
                'title' => ['nullable', 'string', 'max:255'],
                'alt' => ['nullable', 'string', 'max:255'],
            ]);

            $title = $request->input('title');
            $alt = $request->input('alt');

            $files = $request->file('files');
            if (is_array($files) && count($files) > 0) {
                $uploaded = [];
                foreach ($files as $file) {
                    $media = $this->mediaService->store($file, [
                        'title' => $title,
                        'alt' => $alt,
                    ]);
                    $uploaded[] = $this->mediaPayload($media);
                }

                return response()->json([
                    'ok' => true,
                    'message' => 'Dosyalar yüklendi.',
                    'data' => $uploaded,
                ]);
            }

            $media = $this->mediaService->store($request->file('file'), [
                'title' => $title,
                'alt' => $alt,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Dosya yüklendi.',
                'data' => $this->mediaPayload($media),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'error' => ['message' => $exception->getMessage()],
            ], 422);
        }
    }

    public function destroy(Media $media): JsonResponse
    {
        $media->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Medya çöp kutusuna taşındı.',
            'data' => ['deleted' => true],
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $media = Media::onlyTrashed()->findOrFail($id);
        $media->restore();

        return response()->json([
            'ok' => true,
            'message' => 'Medya geri yüklendi.',
            'data' => ['restored' => true],
        ]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $media = Media::withTrashed()->findOrFail($id);
        $media->forceDelete();

        return response()->json([
            'ok' => true,
            'message' => 'Medya kalıcı olarak silindi.',
            'data' => ['force_deleted' => true],
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request->input('ids', []));
        if (count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $count = Media::query()->whereIn('id', $ids)->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Seçili medya kayıtlari silindi.',
            'data' => ['deleted' => $count],
        ]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request->input('ids', []));
        if (count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $count = Media::onlyTrashed()->whereIn('id', $ids)->restore();

        return response()->json([
            'ok' => true,
            'message' => 'Seçili medya kayıtlari geri yüklendi.',
            'data' => ['restored' => $count],
        ]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request->input('ids', []));
        if (count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $items = Media::withTrashed()->whereIn('id', $ids)->get();
        foreach ($items as $media) {
            $media->forceDelete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Seçili medya kayıtlari kalıcı olarak silindi.',
            'data' => ['force_deleted' => $items->count()],
        ]);
    }

    private function mediaPayload(Media $media): array
    {
        return [
            'id' => $media->id,
            'uuid' => $media->uuid,
            'url' => $media->url(),
            'thumb_url' => $media->thumbUrl(),
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
            'size' => (int) $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'is_image' => $media->isImage(),
            'created_at' => $media->created_at?->toDateTimeString(),
            'deleted_at' => $media->deleted_at?->toDateTimeString(),
        ];
    }

    private function validatedIds($ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : []))));
    }

    private function stats(): array
    {
        return [
            'active' => Media::query()->count(),
            'trash' => Media::onlyTrashed()->count(),
            'images' => Media::query()->where('mime_type', 'like', 'image/%')->count(),
            'videos' => Media::query()->where('mime_type', 'like', 'video/%')->count(),
            'pdfs' => Media::query()->where('mime_type', 'application/pdf')->count(),
        ];
    }
}
