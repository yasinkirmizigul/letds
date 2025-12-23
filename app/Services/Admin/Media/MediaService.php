<?php

namespace App\Services\Admin\Media;

use App\Models\Admin\Media\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaService
{
    protected ImageManager $image;

    public function __construct()
    {
        $this->image = new ImageManager(new Driver());
    }

    private function resolveDirectory(?string $forcedDir = null): string
    {
        return $forcedDir
            ? trim($forcedDir, '/')
            : now()->format('Y/m/d');
    }

    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with((string) $file->getClientMimeType(), 'image/');
    }

    public function store(UploadedFile $file, array $attrs = []): Media
    {
        $uuid = (string) Str::uuid();
        $disk = $attrs['disk'] ?? 'public';
        $dir  = $this->resolveDirectory($attrs['dir'] ?? null);

        $extOriginal = strtolower($file->getClientOriginalExtension() ?: 'bin');

        // ---------
        // ORIGINAL
        // ---------
        $originalName = "{$uuid}.{$extOriginal}";
        $originalPath = "{$dir}/{$originalName}";

        Storage::disk($disk)->putFileAs(
            $dir,
            $file,
            $originalName
        );

        $variants = [
            'original' => $originalPath,
        ];

        $width = null;
        $height = null;

        // -----------------
        // IMAGE VARIANTS
        // -----------------
        if ($this->isImage($file)) {
            $img = $this->image->read($file->getRealPath());

            $width  = $img->width();
            $height = $img->height();

            // optimized (max 1920)
            $optimized = clone $img;
            $optimized->scaleDown(width: 1920);

            $optimizedPath = "{$dir}/{$uuid}.webp";
            Storage::disk($disk)->put(
                $optimizedPath,
                (string) $optimized->toWebp(80)
            );

            $variants['optimized'] = $optimizedPath;

            // thumb (400x400)
            $thumb = clone $img;
            $thumb->cover(400, 400);

            $thumbPath = "{$dir}/{$uuid}_thumb.webp";
            Storage::disk($disk)->put(
                $thumbPath,
                (string) $thumb->toWebp(75)
            );

            $variants['thumb'] = $thumbPath;
        }

        return Media::create([
            'uuid'          => $uuid,
            'disk'          => $disk,
            'path'          => $originalPath,   // single source of truth
            'variants'      => $variants,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getClientMimeType(),
            'size'          => $file->getSize(),
            'width'         => $width,
            'height'        => $height,
            'title'         => $attrs['title'] ?? null,
            'alt'           => $attrs['alt'] ?? null,
            'meta'          => $attrs['meta'] ?? null,
        ]);
    }

    public function delete(Media $media): void
    {
        $disk = $media->disk ?? 'public';

        $paths = collect($media->variants ?? [])
            ->push($media->path)
            ->unique();

        foreach ($paths as $path) {
            Storage::disk($disk)->delete($path);
        }

        $media->delete();
    }
}
