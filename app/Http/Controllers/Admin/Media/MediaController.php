<?php

namespace App\Http\Controllers\Admin\Media;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use App\Services\Admin\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            if ($type === 'image') $q->where('mime_type', 'like', 'image/%');
            if ($type === 'video') $q->where('mime_type', 'like', 'video/%');
            if ($type === 'doc') {
                $q->where('mime_type', 'not like', 'image/%')
                    ->where('mime_type', 'not like', 'video/%');
            }
        }

        if ($search = trim((string) $request->get('q'))) {
            $q->where(function ($w) use ($search) {
                $w->where('original_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('alt', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, (int) $request->get('perpage', 24));
        $rows = $q->paginate($perPage);

        return response()->json([
            'ok'   => true,
            'data' => $rows->getCollection()->map(fn (Media $m) => $this->mediaPayload($m))->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page'    => $rows->lastPage(),
                'total'        => $rows->total(),
                'per_page'     => $rows->perPage(),
            ],
        ]);
    }

    /**
     * Upload endpoint (single OR multi).
     * - Single: field "file"
     * - Multi : field "files[]" (multiple)
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file'      => ['required_without:files', 'file', 'max:20480'], // 20MB
            'files'     => ['required_without:file', 'array'],
            'files.*'   => ['file', 'max:20480'], // 20MB each
            'title'     => ['nullable', 'string', 'max:255'],
            'alt'       => ['nullable', 'string', 'max:255'],
        ]);

        $title = $request->input('title');
        $alt   = $request->input('alt');

        $files = $request->file('files');
        if (is_array($files) && count($files)) {
            $uploaded = [];
            $failed   = [];

            foreach ($files as $i => $f) {
                try {
                    $media = $this->mediaService->store($f, [
                        'title' => $title,
                        'alt'   => $alt,
                    ]);
                    $uploaded[] = $this->mediaPayload($media);
                } catch (Throwable $e) {
                    $failed[] = [
                        'index'   => $i,
                        'name'    => method_exists($f, 'getClientOriginalName') ? $f->getClientOriginalName() : null,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'ok'   => true,
                'data' => $uploaded,
                'meta' => [
                    'uploaded' => count($uploaded),
                    'failed'   => count($failed),
                    'failures' => $failed,
                ],
            ]);
        }

        $media = $this->mediaService->store($request->file('file'), [
            'title' => $title,
            'alt'   => $alt,
        ]);

        return response()->json([
            'ok'   => true,
            'data' => $this->mediaPayload($media),
        ]);
    }

    // -------------------------
    // CHUNK UPLOAD v2
    // -------------------------

    public function uploadInit(Request $request): JsonResponse
    {
        $request->validate([
            'original_name' => ['required', 'string', 'max:255'],
            'mime'          => ['nullable', 'string', 'max:255'],
            'size'          => ['required', 'integer', 'min:1'],
            'last_modified' => ['nullable', 'integer'],
            'title'         => ['nullable', 'string', 'max:255'],
            'alt'           => ['nullable', 'string', 'max:255'],
        ]);

        $uploadId = (string) Str::uuid();
        $dir = "tmp/media_uploads/{$uploadId}";
        Storage::disk('local')->makeDirectory($dir);

        $meta = [
            'upload_id'      => $uploadId,
            'original_name'  => $request->string('original_name')->toString(),
            'mime'           => $request->string('mime')->toString(),
            'size'           => (int) $request->integer('size'),
            'last_modified'  => $request->integer('last_modified') ?: null,
            'title'          => $request->input('title'),
            'alt'            => $request->input('alt'),
            'created_at'     => now()->toDateTimeString(),
        ];

        Storage::disk('local')->put("{$dir}/meta.json", json_encode($meta, JSON_UNESCAPED_UNICODE));

        // 5MB default (JS de aynı)
        $chunkSize = 5 * 1024 * 1024;

        return response()->json([
            'ok'   => true,
            'data' => [
                'upload_id'   => $uploadId,
                'chunk_size'  => $chunkSize,
                'already'     => [], // resume istersen burada mevcut chunk index'lerini döndürürsün
            ],
        ]);
    }

    public function uploadChunk(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index'     => ['required', 'integer', 'min:0'],
            'total'     => ['required', 'integer', 'min:1'],
            'chunk'     => ['required', 'file', 'max:51200'], // 50MB chunk limiti
        ]);

        $uploadId = $request->string('upload_id')->toString();
        $index    = (int) $request->integer('index');
        $total    = (int) $request->integer('total');

        $dir = "tmp/media_uploads/{$uploadId}";
        if (!Storage::disk('local')->exists("{$dir}/meta.json")) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'UPLOAD_NOT_FOUND', 'message' => 'Upload session not found'],
            ], 404);
        }

        // chunk yaz
        $chunkFile = $request->file('chunk');
        $path = "{$dir}/{$index}.part";
        Storage::disk('local')->putFileAs($dir, $chunkFile, "{$index}.part");

        // opsiyonel: basit doğrulama
        if (!Storage::disk('local')->exists($path)) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'CHUNK_WRITE_FAILED', 'message' => 'Chunk write failed'],
            ], 500);
        }

        return response()->json([
            'ok'   => true,
            'data' => [
                'upload_id' => $uploadId,
                'index'     => $index,
                'total'     => $total,
            ],
        ]);
    }

    public function uploadFinalize(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => ['required', 'uuid'],
            'total'     => ['nullable', 'integer', 'min:1'],
        ]);

        $uploadId = $request->string('upload_id')->toString();
        $dir = "tmp/media_uploads/{$uploadId}";
        $metaPath = "{$dir}/meta.json";

        if (!Storage::disk('local')->exists($metaPath)) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'UPLOAD_NOT_FOUND', 'message' => 'Upload session not found'],
            ], 404);
        }

        $lock = Cache::lock("media_upload_finalize:{$uploadId}", 15);

        try {
            return $lock->block(5, function () use ($uploadId, $dir, $metaPath) {
                $meta = json_decode(Storage::disk('local')->get($metaPath), true) ?: [];
                $originalName = (string) ($meta['original_name'] ?? 'file.bin');

                // parçaları birleştir
                $tmpMerged = Storage::disk('local')->path("{$dir}/merged.bin");
                $out = fopen($tmpMerged, 'wb');

                if (!$out) {
                    return response()->json([
                        'ok' => false,
                        'error' => ['code' => 'MERGE_FAILED', 'message' => 'Cannot open merge output'],
                    ], 500);
                }

                // chunk indexlerini bul
                $files = collect(Storage::disk('local')->files($dir))
                    ->filter(fn($p) => str_ends_with($p, '.part'))
                    ->values();

                // index sıralama
                $indexes = $files->map(function ($p) {
                    $base = basename($p); // "12.part"
                    return (int) str_replace('.part', '', $base);
                })->sort()->values();

                foreach ($indexes as $idx) {
                    $p = "{$dir}/{$idx}.part";
                    $in = fopen(Storage::disk('local')->path($p), 'rb');
                    if (!$in) {
                        fclose($out);
                        return response()->json([
                            'ok' => false,
                            'error' => ['code' => 'MERGE_FAILED', 'message' => "Cannot read chunk {$idx}"],
                        ], 500);
                    }
                    stream_copy_to_stream($in, $out);
                    fclose($in);
                }

                fclose($out);

                // UploadedFile olarak MediaService'e ver
                $mime = $meta['mime'] ?? null;
                $uploadedFile = new UploadedFile(
                    $tmpMerged,
                    $originalName,
                    $mime ?: null,
                    null,
                    true
                );

                $media = $this->mediaService->store($uploadedFile, [
                    'title' => $meta['title'] ?? null,
                    'alt'   => $meta['alt'] ?? null,
                ]);

                // temizlik
                Storage::disk('local')->deleteDirectory($dir);

                return response()->json([
                    'ok'   => true,
                    'data' => $this->mediaPayload($media),
                ]);
            });
        } finally {
            optional($lock)->release();
        }
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->mediaService->delete($media);
        return response()->json(['ok' => true]);
    }

    private function mediaPayload(Media $m): array
    {
        return [
            'id'            => $m->id,
            'uuid'          => $m->uuid,
            'url'           => $m->url(),
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
