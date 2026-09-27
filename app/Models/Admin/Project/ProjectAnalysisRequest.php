<?php

namespace App\Models\Admin\Project;

use App\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectAnalysisRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['member_id', 'project_file_id', 'message', 'status'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class, 'project_file_id');
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(ProjectFile::class, 'project_analysis_request_files');
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_REVIEWING => 'İnceleniyor',
            self::STATUS_COMPLETED => 'Sonuçlandı',
            default => 'Talep alındı',
        };
    }
}
