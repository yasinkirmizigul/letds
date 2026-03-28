<?php

namespace App\Models\Appointment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalBlackout extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'start_at',
        'end_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }
}
