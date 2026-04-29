<?php

namespace App\Support\Site;

class PaymentProviderRegistry
{
    public static function providers(): array
    {
        return config('payment_integrations.providers', []);
    }

    public static function has(string $provider): bool
    {
        return array_key_exists($provider, self::providers());
    }

    public static function definition(string $provider): array
    {
        return self::providers()[$provider] ?? [];
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::providers() as $provider => $definition) {
            $options[$provider] = (string) ($definition['label'] ?? $provider);
        }

        return $options;
    }

    public static function environmentOptions(): array
    {
        return config('payment_integrations.environment_options', []);
    }

    public static function paymentMethodOptions(): array
    {
        return config('payment_integrations.payment_method_options', []);
    }

    public static function currencyOptions(): array
    {
        return config('payment_integrations.currency_options', []);
    }

    public static function defaultProvider(): string
    {
        return array_key_first(self::providers()) ?: 'iyzico';
    }

    public static function credentialFields(string $provider): array
    {
        return (array) (self::definition($provider)['fields'] ?? []);
    }

    public static function recommendedPaymentMethods(string $provider): array
    {
        return (array) (self::definition($provider)['recommended_payment_methods'] ?? []);
    }

    public static function integrationType(string $provider): string
    {
        return (string) (self::definition($provider)['integration_type'] ?? 'payment_gateway');
    }

    public static function defaultEnvironment(string $provider): string
    {
        return (string) (self::definition($provider)['default_environment'] ?? 'sandbox');
    }

    public static function requiredCredentialKeys(string $provider): array
    {
        $keys = [];

        foreach (self::credentialFields($provider) as $field) {
            if (($field['required'] ?? false) === true) {
                $keys[] = (string) $field['key'];
            }
        }

        return $keys;
    }

    public static function secretCredentialKeys(string $provider): array
    {
        $keys = [];

        foreach (self::credentialFields($provider) as $field) {
            if (($field['secret'] ?? false) === true) {
                $keys[] = (string) $field['key'];
            }
        }

        return $keys;
    }
}
