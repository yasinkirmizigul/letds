<?php

namespace App\Models\Appointment;

use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'provider_id',
        'slot_start_at',
    ];

    protected function casts(): array
    {
        return [
            'slot_start_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
