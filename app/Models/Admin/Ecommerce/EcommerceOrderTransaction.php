<?php

namespace App\Models\Admin\Ecommerce;

use App\Models\Site\PaymentIntegration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceOrderTransaction extends Model
{
    public const TYPE_SALE = 'sale';
    public const TYPE_AUTHORIZATION = 'authorization';
    public const TYPE_CAPTURE = 'capture';
    public const TYPE_REFUND = 'refund';
    public const TYPE_VOID = 'void';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'payment_integration_id',
        'type',
        'status',
        'amount',
        'currency',
        'gateway_transaction_id',
        'gateway_reference',
        'processed_at',
        'payload',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public static function typeOptions(): array
    {
        return [
            self::TYPE_SALE => 'Satış',
            self::TYPE_AUTHORIZATION => 'Provizyon',
            self::TYPE_CAPTURE => 'Tahsilat',
            self::TYPE_REFUND => 'İade',
            self::TYPE_VOID => 'İptal / Void',
            self::TYPE_ADJUSTMENT => 'Manuel Düzeltme',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Bekliyor',
            self::STATUS_SUCCEEDED => 'Başarılı',
            self::STATUS_FAILED => 'Başarısız',
            self::STATUS_CANCELLED => 'İptal',
        ];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCEEDED => 'kt-badge kt-badge-sm kt-badge-light-success',
            self::STATUS_FAILED => 'kt-badge kt-badge-sm kt-badge-light-danger',
            self::STATUS_CANCELLED => 'kt-badge kt-badge-sm kt-badge-light',
            default => 'kt-badge kt-badge-sm kt-badge-light-warning',
        };
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function paymentIntegration(): BelongsTo
    {
        return $this->belongsTo(PaymentIntegration::class);
    }
}
