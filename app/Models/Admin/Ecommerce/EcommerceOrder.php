<?php

namespace App\Models\Admin\Ecommerce;

use App\Models\Admin\User\User;
use App\Models\Member;
use App\Models\Site\PaymentIntegration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EcommerceOrder extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_AWAITING = 'awaiting';
    public const PAYMENT_AUTHORIZED = 'authorized';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';
    public const PAYMENT_REFUNDED = 'refunded';
    public const PAYMENT_FAILED = 'failed';

    public const FULFILLMENT_UNFULFILLED = 'unfulfilled';
    public const FULFILLMENT_PREPARING = 'preparing';
    public const FULFILLMENT_PARTIAL = 'partial';
    public const FULFILLMENT_FULFILLED = 'fulfilled';
    public const FULFILLMENT_RETURNED = 'returned';
    public const FULFILLMENT_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'member_id',
        'payment_integration_id',
        'channel',
        'reference_code',
        'status',
        'payment_status',
        'fulfillment_status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_company',
        'customer_tax_number',
        'customer_tax_office',
        'currency',
        'subtotal',
        'order_discount_total',
        'discount_total',
        'shipping_total',
        'tax_total',
        'grand_total',
        'paid_total',
        'refunded_total',
        'payment_method',
        'shipping_carrier',
        'tracking_number',
        'tracking_url',
        'billing_address',
        'shipping_address',
        'customer_note',
        'internal_note',
        'custom_fields',
        'ordered_at',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'custom_fields' => 'array',
        'subtotal' => 'decimal:2',
        'order_discount_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'refunded_total' => 'decimal:2',
        'ordered_at' => 'datetime',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (EcommerceOrder $order) {
            if (!filled($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        do {
            $candidate = 'EC' . now()->format('ymd') . '-' . Str::upper(Str::random(6));
        } while (self::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => ['label' => 'Taslak', 'badge' => 'kt-badge kt-badge-sm kt-badge-light'],
            self::STATUS_PENDING => ['label' => 'Onay Bekliyor', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning'],
            self::STATUS_CONFIRMED => ['label' => 'Onaylandı', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-primary'],
            self::STATUS_PROCESSING => ['label' => 'Hazırlanıyor', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-info'],
            self::STATUS_SHIPPED => ['label' => 'Kargoda', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-primary'],
            self::STATUS_COMPLETED => ['label' => 'Tamamlandı', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-success'],
            self::STATUS_CANCELLED => ['label' => 'İptal', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger'],
            self::STATUS_REFUNDED => ['label' => 'İade', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger'],
        ];
    }

    public static function paymentStatusOptions(): array
    {
        return [
            self::PAYMENT_UNPAID => ['label' => 'Ödenmedi', 'badge' => 'kt-badge kt-badge-sm kt-badge-light'],
            self::PAYMENT_AWAITING => ['label' => 'Ödeme Bekliyor', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning'],
            self::PAYMENT_AUTHORIZED => ['label' => 'Provizyonda', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-info'],
            self::PAYMENT_PARTIAL => ['label' => 'Kısmi Ödendi', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning'],
            self::PAYMENT_PAID => ['label' => 'Ödendi', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-success'],
            self::PAYMENT_PARTIALLY_REFUNDED => ['label' => 'Kısmi İade', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning'],
            self::PAYMENT_REFUNDED => ['label' => 'İade Edildi', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger'],
            self::PAYMENT_FAILED => ['label' => 'Başarısız', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger'],
        ];
    }

    public static function fulfillmentStatusOptions(): array
    {
        return [
            self::FULFILLMENT_UNFULFILLED => ['label' => 'Bekliyor', 'badge' => 'kt-badge kt-badge-sm kt-badge-light'],
            self::FULFILLMENT_PREPARING => ['label' => 'Hazırlanıyor', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning'],
            self::FULFILLMENT_PARTIAL => ['label' => 'Parçalı Gönderim', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-info'],
            self::FULFILLMENT_FULFILLED => ['label' => 'Gönderildi', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-success'],
            self::FULFILLMENT_RETURNED => ['label' => 'İade Sürecinde', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger'],
            self::FULFILLMENT_CANCELLED => ['label' => 'İptal', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger'],
        ];
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            $builder
                ->where('order_number', 'like', "%{$term}%")
                ->orWhere('reference_code', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('customer_email', 'like', "%{$term}%")
                ->orWhere('customer_phone', 'like', "%{$term}%")
                ->orWhere('tracking_number', 'like', "%{$term}%")
                ->orWhereHas('items', function (Builder $itemQuery) use ($term) {
                    $itemQuery
                        ->where('product_title', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
        });
    }

    public static function optionLabel(array $options, ?string $key): string
    {
        return (string) ($options[$key]['label'] ?? $key ?? '-');
    }

    public static function optionBadge(array $options, ?string $key): string
    {
        return (string) ($options[$key]['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light');
    }

    public function statusLabel(): string
    {
        return self::optionLabel(self::statusOptions(), $this->status);
    }

    public function statusBadgeClass(): string
    {
        return self::optionBadge(self::statusOptions(), $this->status);
    }

    public function paymentStatusLabel(): string
    {
        return self::optionLabel(self::paymentStatusOptions(), $this->payment_status);
    }

    public function paymentStatusBadgeClass(): string
    {
        return self::optionBadge(self::paymentStatusOptions(), $this->payment_status);
    }

    public function fulfillmentStatusLabel(): string
    {
        return self::optionLabel(self::fulfillmentStatusOptions(), $this->fulfillment_status);
    }

    public function fulfillmentStatusBadgeClass(): string
    {
        return self::optionBadge(self::fulfillmentStatusOptions(), $this->fulfillment_status);
    }

    public function balanceDue(): float
    {
        return max(0, (float) $this->grand_total - (float) $this->paid_total + (float) $this->refunded_total);
    }

    public function money(?float $amount = null): string
    {
        return number_format((float) ($amount ?? $this->grand_total), 2, ',', '.') . ' ' . ($this->currency ?: 'TRY');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function paymentIntegration(): BelongsTo
    {
        return $this->belongsTo(PaymentIntegration::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EcommerceOrderItem::class, 'order_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(EcommerceOrderTransaction::class, 'order_id')->latest('processed_at')->latest();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(EcommerceShipment::class, 'order_id')->latest();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(EcommerceOrderStatusHistory::class, 'order_id')->latest();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(EcommerceInvoice::class, 'order_id')->latest();
    }

    public function createdByHistories(): HasMany
    {
        return $this->hasMany(EcommerceOrderStatusHistory::class, 'order_id')->whereNotNull('user_id');
    }
}
