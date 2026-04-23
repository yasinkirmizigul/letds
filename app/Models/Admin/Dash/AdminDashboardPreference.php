<?php

namespace App\Models\Admin\Dash;

use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminDashboardPreference extends Model
{
    protected $fillable = [
        'user_id',
        'visible_sections',
    ];

    protected function casts(): array
    {
        return [
            'visible_sections' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
