<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Media\MediaService;
use App\Support\Audit\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TinyMceController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    public function upload(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAccessAdmin(), 403);

        $file = $request->file('file') ?? $request->file('image');

        if (!$file || !$file->isValid()) {
            return response()->json([
                'error' => ['message' => 'Gecersiz dosya.'],
            ], 422);
        }

        $request->validate([
            'file' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
            'image' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
        ]);

        $media = $this->mediaService->store($file, [
            'disk' => 'public',
            'dir' => 'media/editor',
        ]);

        $url = $media->url('original');

        AuditEvent::log('media.create', [
            'media_id' => (int) $media->id,
            'source' => 'tinymce',
            'disk' => 'public',
            'path' => $media->path,
            'url' => $url,
            'mime' => $media->mime_type,
            'size' => (int) $media->size,
        ]);

        return response()->json([
            'location' => $url,
        ]);
    }
}
