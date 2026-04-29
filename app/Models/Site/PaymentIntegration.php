<?php

namespace App\Models\Site;

use App\Support\Site\PaymentProviderRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentIntegration extends Model
{
    public const ENV_SANDBOX = 'sandbox';
    public const ENV_LIVE = 'live';

    protected $fillable = [
        'provider',
        'title',
        'integration_type',
        'environment',
        'is_active',
        'is_default',
        'sort_order',
        'supported_currencies',
        'allowed_payment_methods',
        'installment_enabled',
        'installment_options',
        'success_url',
        'cancel_url',
        'webhook_ip_whitelist',
        'notes',
        'credentials',
        'credentials_rotated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'supported_currencies' => 'array',
            'allowed_payment_methods' => 'array',
            'installment_enabled' => 'boolean',
            'installment_options' => 'array',
            'credentials' => 'encrypted:array',
            'credentials_rotated_at' => 'datetime',
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
                ->where('title', 'like', "%{$term}%")
                ->orWhere('provider', 'like', "%{$term}%")
                ->orWhere('integration_type', 'like', "%{$term}%");
        });
    }

    public function providerLabel(): string
    {
        return (string) (PaymentProviderRegistry::definition($this->provider)['label'] ?? $this->provider);
    }

    public function providerDescription(): string
    {
        return (string) (PaymentProviderRegistry::definition($this->provider)['description'] ?? '');
    }

    public function environmentLabel(): string
    {
        return (string) (PaymentProviderRegistry::environmentOptions()[$this->environment] ?? $this->environment);
    }

    public function environmentBadgeClass(): string
    {
        return $this->environment === self::ENV_LIVE
            ? 'kt-badge kt-badge-sm kt-badge-light-success'
            : 'kt-badge kt-badge-sm kt-badge-light-warning';
    }

    public function integrationTypeLabel(): string
    {
        return match ((string) $this->integration_type) {
            'virtual_pos' => 'Sanal POS',
            'bank_transfer' => 'Havale / EFT',
            'wallet' => 'Cüzdan / Wallet',
            default => 'Ödeme Ağ Geçidi',
        };
    }

    public function credentialValue(string $key): ?string
    {
        $credentials = is_array($this->credentials) ? $this->credentials : [];
        $value = $credentials[$key] ?? null;

        return filled($value) ? (string) $value : null;
    }

    public function hasCredential(string $key): bool
    {
        return filled($this->credentialValue($key));
    }

    public function hasCompleteRequiredCredentials(): bool
    {
        foreach (PaymentProviderRegistry::requiredCredentialKeys((string) $this->provider) as $key) {
            if (!$this->hasCredential($key)) {
                return false;
            }
        }

        return true;
    }

    public function allowedPaymentMethodLabels(): array
    {
        $options = PaymentProviderRegistry::paymentMethodOptions();

        return collect($this->allowed_payment_methods ?? [])
            ->map(fn ($key) => (string) ($options[$key] ?? $key))
            ->values()
            ->all();
    }

    public function healthBadge(): array
    {
        if (!$this->hasCompleteRequiredCredentials()) {
            return [
                'label' => 'Eksik Yapılandırma',
                'class' => 'kt-badge kt-badge-sm kt-badge-light-danger',
            ];
        }

        if ($this->is_active && $this->environment === self::ENV_LIVE) {
            return [
                'label' => 'Canlıya Hazır',
                'class' => 'kt-badge kt-badge-sm kt-badge-light-success',
            ];
        }

        if ($this->is_active) {
            return [
                'label' => 'Testte Aktif',
                'class' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            ];
        }

        return [
            'label' => 'Pasif',
            'class' => 'kt-badge kt-badge-sm kt-badge-light',
        ];
    }
}
