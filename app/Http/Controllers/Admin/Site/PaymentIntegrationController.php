<?php

namespace App\Http\Controllers\Admin\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\PaymentIntegration;
use App\Support\Site\PaymentProviderRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentIntegrationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status', 'all');
        $environment = (string) $request->string('environment', 'all');

        $integrations = PaymentIntegration::query()
            ->search($search)
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'passive', fn ($query) => $query->where('is_active', false))
            ->when($environment !== 'all', fn ($query) => $query->where('environment', $environment))
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('admin.pages.site.payments.index', [
            'pageTitle' => 'Ödeme Entegrasyonları',
            'integrations' => $integrations,
            'search' => $search,
            'status' => $status,
            'environment' => $environment,
            'providerOptions' => PaymentProviderRegistry::options(),
            'environmentOptions' => PaymentProviderRegistry::environmentOptions(),
            'providerDefinitions' => PaymentProviderRegistry::providers(),
            'stats' => [
                'all' => PaymentIntegration::query()->count(),
                'active' => PaymentIntegration::query()->where('is_active', true)->count(),
                'live' => PaymentIntegration::query()->where('environment', PaymentIntegration::ENV_LIVE)->count(),
                'default' => PaymentIntegration::query()->where('is_default', true)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $provider = $this->resolveProvider((string) $request->string('provider'));

        return view('admin.pages.site.payments.create', [
            'pageTitle' => 'Ödeme Entegrasyonu Ekle',
            'paymentIntegration' => null,
            'selectedProvider' => $provider,
            'suggestedSortOrder' => $this->nextSortOrder(),
            'providerDefinition' => PaymentProviderRegistry::definition($provider),
            'providerDefinitions' => PaymentProviderRegistry::providers(),
            'providerOptions' => PaymentProviderRegistry::options(),
            'environmentOptions' => PaymentProviderRegistry::environmentOptions(),
            'paymentMethodOptions' => PaymentProviderRegistry::paymentMethodOptions(),
            'currencyOptions' => PaymentProviderRegistry::currencyOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $provider = $this->resolveProvider((string) $request->input('provider'));
        $validated = $this->validated($request, $provider);

        $integration = DB::transaction(function () use ($validated, $provider) {
            $integration = PaymentIntegration::create($this->payloadFromValidated($validated, $provider));
            $this->syncDefaultState($integration, (bool) ($validated['is_default'] ?? false));

            if (!$integration->is_default && !PaymentIntegration::query()->where('is_default', true)->exists()) {
                $this->syncDefaultState($integration, true);
            }

            return $integration;
        });

        return redirect()
            ->route('admin.site.payments.edit', $integration)
            ->with('success', 'Ödeme entegrasyonu oluşturuldu.');
    }

    public function edit(PaymentIntegration $paymentIntegration): View
    {
        $provider = (string) $paymentIntegration->provider;

        return view('admin.pages.site.payments.edit', [
            'pageTitle' => 'Ödeme Entegrasyonunu Düzenle',
            'paymentIntegration' => $paymentIntegration,
            'selectedProvider' => $provider,
            'suggestedSortOrder' => (int) $paymentIntegration->sort_order,
            'providerDefinition' => PaymentProviderRegistry::definition($provider),
            'providerDefinitions' => PaymentProviderRegistry::providers(),
            'providerOptions' => PaymentProviderRegistry::options(),
            'environmentOptions' => PaymentProviderRegistry::environmentOptions(),
            'paymentMethodOptions' => PaymentProviderRegistry::paymentMethodOptions(),
            'currencyOptions' => PaymentProviderRegistry::currencyOptions(),
        ]);
    }

    public function update(Request $request, PaymentIntegration $paymentIntegration): RedirectResponse
    {
        $provider = (string) $paymentIntegration->provider;
        $validated = $this->validated($request, $provider, $paymentIntegration);

        DB::transaction(function () use ($paymentIntegration, $validated, $provider) {
            $paymentIntegration->update($this->payloadFromValidated($validated, $provider, $paymentIntegration));
            $this->syncDefaultState($paymentIntegration, (bool) ($validated['is_default'] ?? false));

            if (!$paymentIntegration->is_default && !PaymentIntegration::query()->where('is_default', true)->exists()) {
                $this->promoteFallbackDefault();
            }
        });

        return redirect()
            ->route('admin.site.payments.edit', $paymentIntegration)
            ->with('success', 'Ödeme entegrasyonu güncellendi.');
    }

    public function toggleActive(PaymentIntegration $paymentIntegration): RedirectResponse
    {
        $newState = !$paymentIntegration->is_active;
        $wasDefault = $paymentIntegration->is_default;

        $paymentIntegration->forceFill([
            'is_active' => $newState,
            'is_default' => $newState ? $paymentIntegration->is_default : false,
        ])->save();

        if ($newState && !PaymentIntegration::query()->where('is_default', true)->exists()) {
            $this->syncDefaultState($paymentIntegration, true);
        }

        if (!$newState && $wasDefault) {
            $this->promoteFallbackDefault($paymentIntegration->id);
        }

        return back()->with(
            'success',
            $newState ? 'Ödeme entegrasyonu aktifleştirildi.' : 'Ödeme entegrasyonu pasifleştirildi.'
        );
    }

    public function makeDefault(PaymentIntegration $paymentIntegration): RedirectResponse
    {
        DB::transaction(function () use ($paymentIntegration) {
            $paymentIntegration->forceFill([
                'is_active' => true,
            ])->save();

            $this->syncDefaultState($paymentIntegration, true);
        });

        return back()->with('success', 'Varsayılan ödeme entegrasyonu güncellendi.');
    }

    public function destroy(PaymentIntegration $paymentIntegration): RedirectResponse
    {
        $wasDefault = $paymentIntegration->is_default;
        $deletedId = $paymentIntegration->id;

        $paymentIntegration->delete();

        if ($wasDefault) {
            $this->promoteFallbackDefault($deletedId);
        }

        return redirect()
            ->route('admin.site.payments.index')
            ->with('success', 'Ödeme entegrasyonu silindi.');
    }

    private function resolveProvider(string $provider): string
    {
        return PaymentProviderRegistry::has($provider)
            ? $provider
            : PaymentProviderRegistry::defaultProvider();
    }

    private function validated(
        Request $request,
        string $provider,
        ?PaymentIntegration $paymentIntegration = null
    ): array {
        $environmentOptions = array_keys(PaymentProviderRegistry::environmentOptions());
        $paymentMethodOptions = array_keys(PaymentProviderRegistry::paymentMethodOptions());
        $currencyOptions = array_keys(PaymentProviderRegistry::currencyOptions());

        $rules = [
            'provider' => ['required', Rule::in(array_keys(PaymentProviderRegistry::options()))],
            'title' => ['required', 'string', 'max:120'],
            'environment' => ['required', Rule::in($environmentOptions)],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'supported_currencies' => ['nullable', 'array'],
            'supported_currencies.*' => ['nullable', Rule::in($currencyOptions)],
            'allowed_payment_methods' => ['nullable', 'array'],
            'allowed_payment_methods.*' => ['nullable', Rule::in($paymentMethodOptions)],
            'installment_enabled' => ['nullable', 'boolean'],
            'installment_options' => ['nullable', 'array'],
            'installment_options.*' => ['nullable', 'integer', 'min:2', 'max:24'],
            'success_url' => ['nullable', 'url', 'max:500'],
            'cancel_url' => ['nullable', 'url', 'max:500'],
            'webhook_ip_whitelist' => ['nullable', 'string', 'max:1500'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'credentials' => ['nullable', 'array'],
        ];

        foreach (PaymentProviderRegistry::credentialFields($provider) as $field) {
            $key = (string) $field['key'];
            $type = (string) ($field['type'] ?? 'text');
            $required = ($field['required'] ?? false) === true;
            $hasExistingValue = $paymentIntegration?->hasCredential($key) ?? false;

            $fieldRules = match ($type) {
                'url' => ['nullable', 'url', 'max:500'],
                default => ['nullable', 'string', 'max:1000'],
            };

            if ($required && (!$paymentIntegration || !$hasExistingValue)) {
                array_unshift($fieldRules, 'required');
            }

            $rules['credentials.' . $key] = $fieldRules;
        }

        $validated = $request->validate($rules);

        if (($validated['provider'] ?? null) !== $provider) {
            throw ValidationException::withMessages([
                'provider' => 'Sağlayıcı tipi değiştirilemez. Yeni bir entegrasyon kaydı oluşturmalısınız.',
            ]);
        }

        $installmentOptions = collect($validated['installment_options'] ?? [])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($request->boolean('installment_enabled') && count($installmentOptions) === 0) {
            throw ValidationException::withMessages([
                'installment_options' => 'Taksit özelliği açıksa en az bir taksit seçeneği tanımlamalısınız.',
            ]);
        }

        $this->ensureLiveEnvironmentUsesHttps($validated);

        return array_merge($validated, [
            'installment_options' => $installmentOptions,
            'webhook_ip_whitelist' => $this->normalizeWebhookIpWhitelist($validated['webhook_ip_whitelist'] ?? null),
        ]);
    }

    private function payloadFromValidated(
        array $validated,
        string $provider,
        ?PaymentIntegration $paymentIntegration = null
    ): array {
        $existingCredentials = is_array($paymentIntegration?->credentials) ? $paymentIntegration->credentials : [];
        $incomingCredentials = is_array($validated['credentials'] ?? null) ? $validated['credentials'] : [];
        $normalizedCredentials = [];
        $secretUpdated = false;

        foreach (PaymentProviderRegistry::credentialFields($provider) as $field) {
            $key = (string) $field['key'];
            $incomingValue = trim((string) ($incomingCredentials[$key] ?? ''));
            $isSecret = ($field['secret'] ?? false) === true;

            if ($incomingValue === '' && array_key_exists($key, $existingCredentials)) {
                $normalizedCredentials[$key] = $existingCredentials[$key];
                continue;
            }

            if ($incomingValue !== '') {
                $normalizedCredentials[$key] = $incomingValue;

                if ($isSecret) {
                    $secretUpdated = true;
                }
            }
        }

        return [
            'provider' => $provider,
            'title' => trim((string) $validated['title']),
            'integration_type' => PaymentProviderRegistry::integrationType($provider),
            'environment' => (string) $validated['environment'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? ($paymentIntegration?->sort_order ?? $this->nextSortOrder())),
            'supported_currencies' => collect($validated['supported_currencies'] ?? [])->filter()->unique()->values()->all(),
            'allowed_payment_methods' => collect($validated['allowed_payment_methods'] ?? [])->filter()->unique()->values()->all(),
            'installment_enabled' => (bool) ($validated['installment_enabled'] ?? false),
            'installment_options' => $validated['installment_options'] ?? [],
            'success_url' => filled($validated['success_url'] ?? null) ? trim((string) $validated['success_url']) : null,
            'cancel_url' => filled($validated['cancel_url'] ?? null) ? trim((string) $validated['cancel_url']) : null,
            'webhook_ip_whitelist' => $validated['webhook_ip_whitelist'] ?? null,
            'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            'credentials' => $normalizedCredentials,
            'credentials_rotated_at' => $secretUpdated ? now() : $paymentIntegration?->credentials_rotated_at,
        ];
    }

    private function syncDefaultState(PaymentIntegration $paymentIntegration, bool $isDefault): void
    {
        if (!$isDefault) {
            $paymentIntegration->forceFill([
                'is_default' => false,
            ])->save();

            return;
        }

        PaymentIntegration::query()
            ->whereKeyNot($paymentIntegration->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $paymentIntegration->forceFill([
            'is_default' => true,
            'is_active' => true,
        ])->save();
    }

    private function nextSortOrder(): int
    {
        return ((int) PaymentIntegration::query()->max('sort_order')) + 1;
    }

    private function ensureLiveEnvironmentUsesHttps(array $validated): void
    {
        if (($validated['environment'] ?? null) !== PaymentIntegration::ENV_LIVE) {
            return;
        }

        foreach (['success_url', 'cancel_url'] as $field) {
            $url = trim((string) ($validated[$field] ?? ''));

            if ($url !== '' && !str_starts_with(strtolower($url), 'https://')) {
                throw ValidationException::withMessages([
                    $field => 'Canlı ortamda dönüş URL alanları HTTPS ile başlamalıdır.',
                ]);
            }
        }
    }

    private function normalizeWebhookIpWhitelist(?string $raw): ?string
    {
        if (!filled($raw)) {
            return null;
        }

        $entries = collect(preg_split('/[\s,]+/', (string) $raw) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        foreach ($entries as $entry) {
            if (!$this->isValidIpOrCidr((string) $entry)) {
                throw ValidationException::withMessages([
                    'webhook_ip_whitelist' => 'Webhook IP beyaz listesi yalnızca geçerli IP veya CIDR blokları içerebilir.',
                ]);
            }
        }

        return $entries->implode(PHP_EOL);
    }

    private function isValidIpOrCidr(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (!preg_match('/^([^\/]+)\/(\d{1,3})$/', $value, $matches)) {
            return false;
        }

        $ip = trim((string) ($matches[1] ?? ''));
        $mask = (int) ($matches[2] ?? -1);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $mask >= 0 && $mask <= 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $mask >= 0 && $mask <= 128;
        }

        return false;
    }

    private function promoteFallbackDefault(?int $ignoreId = null): void
    {
        if (PaymentIntegration::query()->where('is_default', true)->exists()) {
            return;
        }

        $fallback = PaymentIntegration::query()
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->first();

        if (!$fallback) {
            return;
        }

        $fallback->forceFill([
            'is_default' => true,
            'is_active' => true,
        ])->save();
    }
}
