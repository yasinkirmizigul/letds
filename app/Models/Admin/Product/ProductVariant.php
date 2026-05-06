<?php

namespace App\Models\Admin\Product;

use App\Models\Admin\Ecommerce\InventoryMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'title',
        'sku',
        'barcode',
        'option_values',
        'price',
        'sale_price',
        'currency',
        'stock',
        'low_stock_threshold',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'option_values' => 'array',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'decimal:3',
        'low_stock_threshold' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function scopeLowStock(Builder $query): Builder
    {
        return $query
            ->whereNotNull('stock')
            ->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function displaySku(): string
    {
        return $this->sku ?: 'SKU yok';
    }

    public function money(): string
    {
        $amount = $this->sale_price ?? $this->price ?? 0;

        return number_format((float) $amount, 2, ',', '.') . ' ' . ($this->currency ?: 'TRY');
    }
}
