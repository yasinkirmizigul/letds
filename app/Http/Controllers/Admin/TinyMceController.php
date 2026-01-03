<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use App\Support\Audit\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TinyMceController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $file = $request->file('file') ?? $request->file('image');

        if (!$file || !$file->isValid()) {
            return response()->json([
                'error' => ['message' => 'Geçersiz dosya.'],
            ], 422);
        }

        $request->validate([
            'file' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
            'image' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
        ]);

        $ext = strtolower($file->getClientOriginalExtension());
        $uuid = Str::uuid()->toString();
        $filename = $uuid . '.' . $ext;

        // storage/app/public/media/editor
        $path = $file->storeAs('media/editor', $filename, 'public');
        $url = Storage::disk('public')->url($path);

        // --------------------
        // Media kaydı
        // --------------------
        $media = Media::create([
            'disk' => 'public',
            'directory' => 'media/editor',
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $ext,
            'type' => 'image',
        ]);

        // --------------------
        // Audit log
        // --------------------
        AuditEvent::log('media.create', [
            'media_id' => (int) $media->id,
            'source' => 'tinymce',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $media->mime_type,
            'size' => (int) $media->size,
        ]);

        // --------------------
        // TinyMCE response
        // --------------------
        return response()->json([
            'location' => $url,
        ]);
    }
}
