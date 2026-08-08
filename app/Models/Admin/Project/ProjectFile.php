<?php

namespace App\Models\Admin\Project;

use App\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProjectFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'member_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'note',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected static function booted(): void
    {
        static::forceDeleted(function (self $file): void {
            Storage::disk($file->disk)->delete($file->path);
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function sizeLabel(): string
    {
        $bytes = max(0, (int) $this->size);
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return number_format($value, $unit === 0 ? 0 : 1, ',', '.').' '.$units[$unit];
    }
}
