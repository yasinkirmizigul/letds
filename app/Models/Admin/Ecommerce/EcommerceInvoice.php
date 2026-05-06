<?php

namespace App\Models\Admin\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EcommerceInvoice extends Model
{
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_PROFORMA = 'proforma';
    public const TYPE_REFUND = 'refund';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'invoice_number',
        'type',
        'status',
        'currency',
        'subtotal',
        'tax_total',
        'grand_total',
        'billing_snapshot',
        'line_snapshot',
        'pdf_path',
        'issued_at',
        'due_at',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'billing_snapshot' => 'array',
        'line_snapshot' => 'array',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (EcommerceInvoice $invoice) {
            if (!filled($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber($invoice->type ?: self::TYPE_INVOICE);
            }
        });
    }

    public static function generateInvoiceNumber(string $type): string
    {
        $prefix = match ($type) {
            self::TYPE_PROFORMA => 'PRF',
            self::TYPE_REFUND => 'IAD',
            default => 'FTR',
        };

        do {
            $candidate = $prefix . now()->format('ymd') . '-' . Str::upper(Str::random(5));
        } while (self::query()->where('invoice_number', $candidate)->exists());

        return $candidate;
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_INVOICE => 'Fatura',
            self::TYPE_PROFORMA => 'Proforma',
            self::TYPE_REFUND => 'İade Belgesi',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Taslak',
            self::STATUS_ISSUED => 'Kesildi',
            self::STATUS_CANCELLED => 'İptal',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function money(): string
    {
        return number_format((float) $this->grand_total, 2, ',', '.') . ' ' . ($this->currency ?: 'TRY');
    }
}
