<?php

namespace App\Models\Admin\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceShipment extends Model
{
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'status',
        'carrier',
        'tracking_number',
        'tracking_url',
        'package_count',
        'address',
        'shipped_at',
        'delivered_at',
        'notes',
    ];

    protected $casts = [
        'address' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PREPARING => 'Hazırlanıyor',
            self::STATUS_READY => 'Kargoya Hazır',
            self::STATUS_SHIPPED => 'Kargoda',
            self::STATUS_DELIVERED => 'Teslim Edildi',
            self::STATUS_RETURNED => 'İade',
            self::STATUS_CANCELLED => 'İptal',
        ];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DELIVERED => 'kt-badge kt-badge-sm kt-badge-light-success',
            self::STATUS_SHIPPED => 'kt-badge kt-badge-sm kt-badge-light-primary',
            self::STATUS_RETURNED, self::STATUS_CANCELLED => 'kt-badge kt-badge-sm kt-badge-light-danger',
            default => 'kt-badge kt-badge-sm kt-badge-light-warning',
        };
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }
}
