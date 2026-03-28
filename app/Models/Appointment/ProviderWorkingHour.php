<?php

namespace App\Models\Appointment;

use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderWorkingHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'day_of_week',
        'is_enabled',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'day_of_week' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
