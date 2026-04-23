<?php

namespace App\Models;

use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    public const SENDER_TYPE_MEMBER = 'member';
    public const SENDER_TYPE_GUEST = 'guest';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const CONTACT_CHANNEL_EMAIL = 'email';
    public const CONTACT_CHANNEL_PHONE = 'phone';

    public const PRIORITY_OPTIONS = [
        self::PRIORITY_LOW => [
            'label' => 'Düşük',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light',
            'order' => 10,
        ],
        self::PRIORITY_NORMAL => [
            'label' => 'Normal',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-primary',
            'order' => 20,
        ],
        self::PRIORITY_HIGH => [
            'label' => 'Yüksek',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            'order' => 30,
        ],
        self::PRIORITY_URGENT => [
            'label' => 'Acil',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger',
            'order' => 40,
        ],
    ];

    public const CONTACT_CHANNEL_OPTIONS = [
        self::CONTACT_CHANNEL_EMAIL => [
            'label' => 'E-posta',
            'order' => 10,
        ],
        self::CONTACT_CHANNEL_PHONE => [
            'label' => 'Telefon',
            'order' => 20,
        ],
    ];

    protected $fillable = [
        'recipient_user_id',
        'member_id',
        'recipient_name',
        'sender_type',
        'sender_name',
        'sender_surname',
        'sender_email',
        'sender_phone',
        'preferred_channels',
        'subject',
        'priority',
        'message',
        'read_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'preferred_channels' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public static function priorityOptionsSorted(): array
    {
        $options = self::PRIORITY_OPTIONS;
        uasort($options, fn ($a, $b) => (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0));

        return $options;
    }

    public static function priorityLabel(?string $key): string
    {
        $key = $key ?: self::PRIORITY_NORMAL;

        return self::PRIORITY_OPTIONS[$key]['label'] ?? $key;
    }

    public static function priorityBadgeClass(?string $key): string
    {
        $key = $key ?: self::PRIORITY_NORMAL;

        return self::PRIORITY_OPTIONS[$key]['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light';
    }

    public static function senderTypeLabel(?string $type): string
    {
        return match ($type) {
            self::SENDER_TYPE_MEMBER => 'Üye',
            self::SENDER_TYPE_GUEST => 'Ziyaretçi',
            default => 'Bilinmiyor',
        };
    }

    public static function senderTypeBadgeClass(?string $type): string
    {
        return match ($type) {
            self::SENDER_TYPE_MEMBER => 'kt-badge kt-badge-sm kt-badge-light-success',
            self::SENDER_TYPE_GUEST => 'kt-badge kt-badge-sm kt-badge-light-primary',
            default => 'kt-badge kt-badge-sm kt-badge-light',
        };
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('recipient_user_id', $user->id);
    }

    public function isVisibleToUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $this->recipient_user_id === (int) $user->id;
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function getSenderFullNameAttribute(): string
    {
        return trim(($this->sender_name ?? '') . ' ' . ($this->sender_surname ?? ''));
    }

    public function getRecipientDisplayNameAttribute(): string
    {
        return $this->recipient?->name ?: ($this->recipient_name ?: 'Silinmiş kullanıcı');
    }

    public function preferredChannelsLabel(): string
    {
        if ($this->sender_type === self::SENDER_TYPE_MEMBER) {
            return 'Üye profili';
        }

        $channels = collect($this->preferred_channels ?? [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => self::CONTACT_CHANNEL_OPTIONS[$value]['label'] ?? $value)
            ->values();

        if ($channels->isEmpty()) {
            return '-';
        }

        return $channels->implode(', ');
    }
}
