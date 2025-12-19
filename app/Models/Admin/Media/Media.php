<?php
namespace App\Models\Admin\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'uuid','disk','path','original_name','mime_type','size',
        'width','height','title','alt','meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
