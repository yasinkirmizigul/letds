<?php

namespace App\Models\Appointment;

use App\Models\Admin\User\User;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Appointment\AppointmentSlot;
use App\Models\Concerns\HasLocalDateTimes;
class Appointment extends Model
{
    use HasFactory, HasLocalDateTimes;

    public const STATUS_BOOKED = 'booked';
    public const STATUS_CANCELLED_BY_PROVIDER = 'cancelled_by_provider';
    public const STATUS_CANCELLED_BY_MEMBER = 'cancelled_by_member';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_TRANSFERRED = 'transferred';

    protected $fillable = [
        'provider_id',
        'member_id',
        'start_at',
        'end_at',
        'blocks',
        'status',
        'notes_internal',
        'cancelled_at',
        'cancel_reason',
        'cancelled_by_user_id',
        'created_by_user_id',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'blocks' => 'integer',
        ];
    }
    public function root(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function latestDescendant(): ?self
    {
        return $this->children()->latest('id')->first();
    }

    public function historyChain()
    {
        return self::query()
            ->where(function ($q) {
                $q->where('id', $this->id)
                    ->orWhere('id', $this->parent_id)
                    ->orWhere('parent_id', $this->id)
                    ->orWhere('parent_id', $this->parent_id);
            })
            ->orderBy('start_at')
            ->get();
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isActiveBooking(): bool
    {
        return $this->status === self::STATUS_BOOKED
            && $this->end_at
            && $this->end_at->gte(\Carbon\Carbon::now('UTC'));
    }
}
