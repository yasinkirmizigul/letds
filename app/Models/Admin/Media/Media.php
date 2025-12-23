<?php

namespace App\Models\Admin\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'disk',
        'path',
        'variants',        // ✅ eklendi
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
        'variants' => 'array', // ✅ eklendi
        'meta'     => 'array',
        'size'     => 'integer',
        'width'    => 'integer',
        'height'   => 'integer',
    ];

    public function url(string $variant = 'optimized'): string
    {
        $disk = $this->disk ?? 'public';
        $variants = is_array($this->variants) ? $this->variants : [];

        if (isset($variants[$variant]) && $variants[$variant]) {
            return Storage::disk($disk)->url($variants[$variant]);
        }

        // fallback sırası
        if (isset($variants['original']) && $variants['original']) {
            return Storage::disk($disk)->url($variants['original']);
        }

        return Storage::disk($disk)->url($this->path);
    }

    // İstersen grid için bunu kullan:
    public function thumbUrl(): string
    {
        return $this->url('thumb');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
