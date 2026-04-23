<?php

namespace App\Models\Admin\Project;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTranslation extends Model
{
    protected $table = 'project_translations';

    protected $fillable = [
        'project_id',
        'locale',
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
