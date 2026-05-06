<?php

namespace App\Models\Admin\Ecommerce;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EcommerceCoupon extends Model
{
    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FREE_SHIPPING = 'free_shipping';

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_order_total',
        'max_discount_total',
        'usage_limit',
        'usage_count',
        'per_customer_limit',
        'applies_to',
        'is_active',
        'starts_at',
        'ends_at',
        'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_total' => 'decimal:2',
        'max_discount_total' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_customer_limit' => 'integer',
        'applies_to' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public static function typeOptions(): array
    {
        return [
            self::TYPE_FIXED => 'Sabit Tutar',
            self::TYPE_PERCENTAGE => 'Yüzde',
            self::TYPE_FREE_SHIPPING => 'Ücretsiz Kargo',
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
                ->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%");
        });
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? $this->type;
    }

    public function statusBadgeClass(): string
    {
        if (!$this->is_active) {
            return 'kt-badge kt-badge-sm kt-badge-light';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'kt-badge kt-badge-sm kt-badge-light-danger';
        }

        return 'kt-badge kt-badge-sm kt-badge-light-success';
    }

    public function statusLabel(): string
    {
        if (!$this->is_active) {
            return 'Pasif';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'Süresi Bitti';
        }

        return 'Aktif';
    }
}
