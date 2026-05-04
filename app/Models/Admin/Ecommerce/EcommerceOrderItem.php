<?php

namespace App\Models\Admin\Ecommerce;

use App\Models\Admin\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceOrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_title',
        'sku',
        'barcode',
        'brand',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_total',
        'tax_rate',
        'tax_total',
        'total',
        'currency',
        'fulfillment_status',
        'custom_fields',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'custom_fields' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
