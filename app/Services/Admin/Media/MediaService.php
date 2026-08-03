<?php

namespace App\Services\Admin\Media;

use App\Models\Admin\Media\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MediaService
{
    private const FULL_MAX_DIMENSION = 2560;

    private const OPTIMIZED_MAX_DIMENSION = 1920;

    private const THUMB_SIZE = 400;

    protected ImageManager $image;

    public function __construct()
    {
        $this->image = ImageManager::usingDriver(Driver::class);
    }

    private function resolveDirectory(?string $forcedDir = null): string
    {
        return $forcedDir
            ? trim($forcedDir, '/')
            : now()->format('Y/m/d');
    }

    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with((string) ($file->getMimeType() ?: $file->getClientMimeType()), 'image/');
    }

    private function convertsOriginalToWebp(UploadedFile $file): bool
    {
        return in_array(
            strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType())),
            ['image/jpeg', 'image/png', 'image/webp'],
            true
        );
    }

    public function store(UploadedFile $file, array $attrs = []): Media
    {
        $uuid = (string) Str::uuid();
        $disk = $attrs['disk'] ?? 'public';
        $dir = $this->resolveDirectory($attrs['dir'] ?? null);

        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType());
        $storedSize = (int) $file->getSize();
        $variants = [];
        $width = null;
        $height = null;
        $originalPath = '';

        if ($this->isImage($file)) {
            $img = $this->image->decode($file->getRealPath());
            $width = $img->width();
            $height = $img->height();

            if ($this->convertsOriginalToWebp($file)) {
                $full = clone $img;
                $full->scaleDown(width: self::FULL_MAX_DIMENSION, height: self::FULL_MAX_DIMENSION);
                $fullWebp = (string) $full->encodeUsingFileExtension('webp', quality: 85);
                $originalPath = "{$dir}/{$uuid}.webp";
                Storage::disk($disk)->put($originalPath, $fullWebp);

                $mimeType = 'image/webp';
                $storedSize = strlen($fullWebp);
                $variants['original'] = $originalPath;

                if ($width > self::OPTIMIZED_MAX_DIMENSION || $height > self::OPTIMIZED_MAX_DIMENSION) {
                    $optimized = clone $img;
                    $optimized->scaleDown(
                        width: self::OPTIMIZED_MAX_DIMENSION,
                        height: self::OPTIMIZED_MAX_DIMENSION
                    );
                    $optimizedPath = "{$dir}/{$uuid}_optimized.webp";
                    Storage::disk($disk)->put(
                        $optimizedPath,
                        (string) $optimized->encodeUsingFileExtension('webp', quality: 80)
                    );
                    $variants['optimized'] = $optimizedPath;
                } else {
                    $variants['optimized'] = $originalPath;
                }
            } else {
                $extOriginal = strtolower($file->getClientOriginalExtension() ?: 'bin');
                $originalName = "{$uuid}.{$extOriginal}";
                $originalPath = "{$dir}/{$originalName}";
                Storage::disk($disk)->putFileAs($dir, $file, $originalName);
                $variants['original'] = $originalPath;

                $optimized = clone $img;
                $optimized->scaleDown(
                    width: self::OPTIMIZED_MAX_DIMENSION,
                    height: self::OPTIMIZED_MAX_DIMENSION
                );
                $optimizedPath = "{$dir}/{$uuid}_optimized.webp";
                Storage::disk($disk)->put(
                    $optimizedPath,
                    (string) $optimized->encodeUsingFileExtension('webp', quality: 80)
                );
                $variants['optimized'] = $optimizedPath;
            }

            $thumb = clone $img;
            $thumb->cover(self::THUMB_SIZE, self::THUMB_SIZE);

            $thumbPath = "{$dir}/{$uuid}_thumb.webp";
            Storage::disk($disk)->put(
                $thumbPath,
                (string) $thumb->encodeUsingFileExtension('webp', quality: 75)
            );
            $variants['thumb'] = $thumbPath;
        } else {
            $extOriginal = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $originalName = "{$uuid}.{$extOriginal}";
            $originalPath = "{$dir}/{$originalName}";
            Storage::disk($disk)->putFileAs($dir, $file, $originalName);
            $variants['original'] = $originalPath;
        }

        return Media::create([
            'uuid' => $uuid,
            'disk' => $disk,
            'path' => $originalPath,
            'variants' => $variants,
            'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'mime_type' => $mimeType,
            'size' => $storedSize,
            'width' => $width,
            'height' => $height,
            'title' => $attrs['title'] ?? null,
            'alt' => $attrs['alt'] ?? null,
            'meta' => $attrs['meta'] ?? null,
        ]);
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = basename(trim($name));
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'dosya';
        $name = trim($name, '.-');

        return $name !== '' ? $name : 'dosya';
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
