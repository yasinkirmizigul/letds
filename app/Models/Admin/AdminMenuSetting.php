<?php

namespace App\Models\Admin;

use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMenuSetting extends Model
{
    public const SINGLETON_ID = 1;

    protected $fillable = [
        'id',
        'hidden_items',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'hidden_items' => 'array',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
