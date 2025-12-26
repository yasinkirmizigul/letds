<?php

namespace App\Models\Admin\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'disk',
        'path',
        'variants',
        'original_name',
        'mime_type',
        'size',
        'width',
        'height',
        'title',
        'alt',
        'meta',
    ];

    protected $casts = [
        'variants' => 'array',
        'meta'     => 'array',
        'size'     => 'integer',
        'width'    => 'integer',
        'height'   => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // forceDelete => fiziksel dosyaları kaldır
        static::forceDeleted(function (Media $media) {
            $disk = $media->disk ?? config('filesystems.default');
            $toDelete = [];

            if (!empty($media->path)) {
                $toDelete[] = $media->path;
            }

            $variants = is_array($media->variants) ? $media->variants : [];
            foreach ($variants as $p) {
                if (!empty($p)) $toDelete[] = $p;
            }

            $toDelete = array_values(array_unique($toDelete));
            if ($toDelete) {
                Storage::disk($disk)->delete($toDelete);
            }
        });
    }

    public function url(string $variant = 'optimized'): string
    {
        $disk = $this->disk ?? 'public';
        $variants = is_array($this->variants) ? $this->variants : [];

        if (!empty($variants[$variant])) {
            return Storage::disk($disk)->url($variants[$variant]);
        }

        if (!empty($variants['original'])) {
            return Storage::disk($disk)->url($variants['original']);
        }

        return Storage::disk($disk)->url($this->path);
    }

    public function thumbUrl(): string
    {
        return $this->url('thumb');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
