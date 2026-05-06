<?php

namespace App\Models\Admin\Ecommerce;

use App\Models\Site\PaymentIntegration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'payment_integration_id',
        'order_id',
        'provider',
        'event_type',
        'event_id',
        'status',
        'headers',
        'payload',
        'received_at',
        'processed_at',
        'error_message',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_RECEIVED => 'Alındı',
            self::STATUS_PROCESSED => 'İşlendi',
            self::STATUS_FAILED => 'Hatalı',
            self::STATUS_IGNORED => 'Yok Sayıldı',
        ];
    }

    public function paymentIntegration(): BelongsTo
    {
        return $this->belongsTo(PaymentIntegration::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }
}
