<?php

namespace App\Models\Admin\Ecommerce;

use App\Models\Admin\Product\Product;
use App\Models\Admin\Product\ProductVariant;
use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RESERVATION = 'reservation';
    public const TYPE_RELEASE = 'release';
    public const TYPE_RETURN = 'return';
    public const TYPE_DAMAGE = 'damage';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'order_id',
        'user_id',
        'type',
        'reason',
        'quantity',
        'before_stock',
        'after_stock',
        'reference',
        'notes',
        'occurred_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'before_stock' => 'decimal:3',
        'after_stock' => 'decimal:3',
        'occurred_at' => 'datetime',
    ];

    public static function typeOptions(): array
    {
        return [
            self::TYPE_IN => 'Stok Girişi',
            self::TYPE_OUT => 'Stok Çıkışı',
            self::TYPE_ADJUSTMENT => 'Stok Düzeltme',
            self::TYPE_RESERVATION => 'Rezervasyon',
            self::TYPE_RELEASE => 'Rezervasyon Çözme',
            self::TYPE_RETURN => 'İade Girişi',
            self::TYPE_DAMAGE => 'Fire / Hasar',
        ];
    }

    public static function signedQuantity(string $type, float $quantity): float
    {
        return match ($type) {
            self::TYPE_OUT, self::TYPE_RESERVATION, self::TYPE_DAMAGE => -abs($quantity),
            default => abs($quantity),
        };
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
