<?php
namespace App\Services\Admin\Media;

use App\Models\Admin\Media\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function store(UploadedFile $file, array $attrs = []): Media
    {
        $uuid = (string) Str::uuid();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');

        $dir = 'media/' . now()->format('Y/m');
        $path = $dir . '/' . $uuid . '.' . $ext;

        Storage::disk('public')->putFileAs($dir, $file, $uuid . '.' . $ext);

        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $size = $file->getSize() ?: 0;

        $width = null; $height = null;
        if (str_starts_with($mime, 'image/')) {
            // hafif: getimagesize storage path üzerinden
            $abs = Storage::disk('public')->path($path);
            $info = @getimagesize($abs);
            if ($info) { $width = $info[0] ?? null; $height = $info[1] ?? null; }
        }

        return Media::create([
            'uuid' => $uuid,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'title' => $attrs['title'] ?? null,
            'alt' => $attrs['alt'] ?? null,
            'meta' => $attrs['meta'] ?? null,
        ]);
    }

    public function delete(Media $media): void
    {
        DB::transaction(function () use ($media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        });
    }
}
