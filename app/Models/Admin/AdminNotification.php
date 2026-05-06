<?php

namespace App\Models\Admin;

use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_SUCCESS = 'success';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_DANGER = 'danger';

    public const TYPE_SYSTEM = 'system';
    public const TYPE_MESSAGE = 'message';
    public const TYPE_APPOINTMENT = 'appointment';
    public const TYPE_ORDER = 'order';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_INVENTORY = 'inventory';

    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'title',
        'body',
        'action_label',
        'action_url',
        'source_type',
        'source_id',
        'data',
        'read_at',
        'dismissed_at',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $user->id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('dismissed_at')
            ->where(function (Builder $builder) {
                $builder
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function dismiss(): void
    {
        $this->forceFill([
            'read_at' => $this->read_at ?: now(),
            'dismissed_at' => now(),
        ])->save();
    }

    public function severityBadgeClass(): string
    {
        return match ($this->severity) {
            self::SEVERITY_SUCCESS => 'kt-badge kt-badge-sm kt-badge-light-success',
            self::SEVERITY_WARNING => 'kt-badge kt-badge-sm kt-badge-light-warning',
            self::SEVERITY_DANGER => 'kt-badge kt-badge-sm kt-badge-light-danger',
            default => 'kt-badge kt-badge-sm kt-badge-light-primary',
        };
    }

    public function iconClass(): string
    {
        return match ($this->type) {
            self::TYPE_MESSAGE => 'ki-filled ki-messages',
            self::TYPE_APPOINTMENT => 'ki-filled ki-calendar-8',
            self::TYPE_ORDER => 'ki-filled ki-basket',
            self::TYPE_PAYMENT => 'ki-filled ki-two-credit-cart',
            self::TYPE_INVENTORY => 'ki-filled ki-handcart',
            default => 'ki-filled ki-notification-status',
        };
    }

    public function severityLabel(): string
    {
        return match ($this->severity) {
            self::SEVERITY_SUCCESS => 'Bilgi',
            self::SEVERITY_WARNING => 'Uyarı',
            self::SEVERITY_DANGER => 'Kritik',
            default => 'Bilgilendirme',
        };
    }
}
