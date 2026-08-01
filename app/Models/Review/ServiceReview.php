<?php

namespace App\Models\Review;

use App\Models\Admin\User\User;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ServiceReview extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const SERVICE_APPOINTMENT = 'appointment';

    public const SERVICE_ORDER = 'order';

    protected $fillable = [
        'member_id',
        'provider_user_id',
        'reviewable_type',
        'reviewable_id',
        'service_type',
        'service_title',
        'service_reference',
        'status',
        'overall_rating',
        'public_comment',
        'service_completed_at',
        'invited_at',
        'questions_locked_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating' => 'integer',
            'service_completed_at' => 'datetime',
            'invited_at' => 'datetime',
            'questions_locked_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_user_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceReviewItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return $this->isPending() ? 'Yanıt Bekliyor' : 'Tamamlandı';
    }

    public function statusBadgeClass(): string
    {
        return $this->isPending()
            ? 'kt-badge kt-badge-sm kt-badge-light-warning'
            : 'kt-badge kt-badge-sm kt-badge-light-success';
    }

    public function serviceTypeLabel(): string
    {
        return match ($this->service_type) {
            self::SERVICE_APPOINTMENT => 'Randevu',
            self::SERVICE_ORDER => 'Sipariş',
            default => 'Hizmet',
        };
    }
}
