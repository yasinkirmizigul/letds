@php
    $isEdit = $paymentIntegration !== null;
    $credentialFields = (array) ($providerDefinition['fields'] ?? []);
    $recommendedMethods = (array) ($providerDefinition['recommended_payment_methods'] ?? []);
    $selectedCurrencies = old('supported_currencies', $paymentIntegration?->supported_currencies ?? ['TRY']);
    $selectedPaymentMethods = old('allowed_payment_methods', $paymentIntegration?->allowed_payment_methods ?? $recommendedMethods);
    $selectedInstallments = collect(old('installment_options', $paymentIntegration?->installment_options ?? [2, 3, 6, 9]))->map(fn ($value) => (int) $value)->all();
    $installmentEnabled = old('installment_enabled', $paymentIntegration?->installment_enabled ?? in_array('installment', $selectedPaymentMethods, true));
@endphp

<form method="POST" action="{{ $formAction }}" class="grid gap-6">
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <input type="hidden" name="provider" value="{{ $selectedProvider }}">

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_420px]">
        <div class="grid gap-6">
            <div class="rounded-[32px] app-surface-card p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="grid gap-2">
                        <div class="text-xs uppercase tracking-[0.24em] text-primary">Sağlayıcı Şablonu</div>
                        <div class="text-2xl font-semibold text-foreground">{{ $providerDefinition['label'] ?? $selectedProvider }}</div>
                        <div class="max-w-3xl text-sm leading-7 text-muted-foreground">{{ $providerDefinition['description'] ?? '' }}</div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="kt-badge kt-badge-sm kt-badge-light">{{ $providerDefinition['integration_type'] ?? 'payment_gateway' }}</span>
                        <span class="kt-badge kt-badge-sm kt-badge-light-warning">{{ $providerDefinition['default_environment'] ?? 'sandbox' }}</span>
                    </div>
                </div>
            </div>

            @unless($isEdit)
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($providerOptions as $providerKey => $providerLabel)
                        <a href="{{ route('admin.site.payments.create', ['provider' => $providerKey]) }}" class="rounded-[28px] border {{ $providerKey === $selectedProvider ? 'border-primary bg-primary/5' : 'border-border bg-background/80' }} p-5 text-foreground transition hover:-translate-y-0.5">
                            <div class="font-semibold text-foreground">{{ $providerLabel }}</div>
                            <div class="mt-2 text-sm leading-7 text-muted-foreground">{{ $providerDefinitions[$providerKey]['description'] ?? '' }}</div>
                        </a>
                    @endforeach
                </div>
            @endunless

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Genel Yapılandırma</h3>
                        <div class="text-sm text-muted-foreground">Başlık, ortam ve aktiflik gibi temel kararları bu bölümden ver.</div>
                    </div>
                </div>

                <div class="kt-card-content grid gap-4 p-6 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Kayıt Başlığı</label>
                        <input name="title" class="kt-input @error('title') kt-input-invalid @enderror" value="{{ old('title', $paymentIntegration?->title ?: ($providerDefinition['label'] ?? $selectedProvider)) }}" placeholder="Örn. iyzico Canlı POS">
                        @error('title')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Çalışma Ortamı</label>
                        <select name="environment" class="kt-select @error('environment') kt-input-invalid @enderror" data-kt-select="true">
                            @foreach($environmentOptions as $environmentKey => $label)
                                <option value="{{ $environmentKey }}" @selected(old('environment', $paymentIntegration?->environment ?: ($providerDefinition['default_environment'] ?? 'sandbox')) === $environmentKey)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('environment')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Sıra</label>
                        <input name="sort_order" type="number" min="0" class="kt-input @error('sort_order') kt-input-invalid @enderror" value="{{ old('sort_order', $paymentIntegration?->sort_order ?? ($suggestedSortOrder ?? 1)) }}">
                        @error('sort_order')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-3">
                        <label class="kt-form-label">Durum</label>
                        <label class="flex items-start gap-3 rounded-2xl border border-border bg-muted/20 px-4 py-4 text-sm text-muted-foreground">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="kt-checkbox mt-1" @checked(old('is_active', $paymentIntegration?->is_active ?? false))>
                            <span>Bu entegrasyon yönetim tarafında aktif olarak kullanılabilir.</span>
                        </label>
                        <label class="flex items-start gap-3 rounded-2xl border border-border bg-muted/20 px-4 py-4 text-sm text-muted-foreground">
                            <input type="hidden" name="is_default" value="0">
                            <input type="checkbox" name="is_default" value="1" class="kt-checkbox mt-1" @checked(old('is_default', $paymentIntegration?->is_default ?? false))>
                            <span>Bu kaydı varsayılan ödeme sağlayıcısı olarak işaretle.</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Credential ve Kimlik Bilgileri</h3>
                        <div class="text-sm text-muted-foreground">Gizli anahtarlar şifreli saklanır ve tekrar görüntülenmez.</div>
                    </div>
                </div>

                <div class="kt-card-content grid gap-4 p-6 lg:grid-cols-2">
                    @foreach($credentialFields as $field)
                        @php
                            $fieldKey = (string) $field['key'];
                            $isSecretField = ($field['secret'] ?? false) === true;
                            $storedValue = $paymentIntegration?->credentialValue($fieldKey);
                            $inputValue = $isSecretField ? '' : old('credentials.' . $fieldKey, $storedValue ?: ($field['default'] ?? ''));
                            $errorKey = 'credentials.' . $fieldKey;
                        @endphp

                        <div class="grid gap-2 {{ ($field['type'] ?? '') === 'textarea' ? 'lg:col-span-2' : '' }}">
                            <label class="kt-form-label">{{ $field['label'] }} @if(($field['required'] ?? false) === true)<span class="text-danger">*</span>@endif</label>

                            @if(($field['type'] ?? 'text') === 'url')
                                <input
                                    name="credentials[{{ $fieldKey }}]"
                                    type="url"
                                    class="kt-input @error($errorKey) kt-input-invalid @enderror"
                                    value="{{ $inputValue }}"
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                >
                            @else
                                <input
                                    name="credentials[{{ $fieldKey }}]"
                                    type="{{ $isSecretField ? 'password' : 'text' }}"
                                    class="kt-input @error($errorKey) kt-input-invalid @enderror"
                                    value="{{ $inputValue }}"
                                    placeholder="{{ $isSecretField && $storedValue ? 'Kayıtlı değeri korumak için boş bırak' : ($field['placeholder'] ?? '') }}"
                                    autocomplete="off"
                                >
                            @endif

                            @if($isSecretField)
                                <div class="text-xs text-muted-foreground">
                                    {{ $storedValue ? 'Bu secret alan daha önce kaydedildi. Yeni değer girmezsen mevcut secret korunur.' : 'Bu alan şifreli olarak saklanacaktır.' }}
                                </div>
                            @endif

                            @error($errorKey)
                                <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Ödeme Davranışı</h3>
                        <div class="text-sm text-muted-foreground">Para birimi, yöntem ve taksit kabiliyetlerini merkezi olarak belirle.</div>
                    </div>
                </div>

                <div class="kt-card-content grid gap-6 p-6">
                    <div class="grid gap-3">
                        <label class="kt-form-label">Desteklenen Para Birimleri</label>
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach($currencyOptions as $currencyCode => $label)
                                <label class="flex items-start gap-3 rounded-2xl border border-border bg-muted/20 px-4 py-4 text-sm text-muted-foreground">
                                    <input type="checkbox" name="supported_currencies[]" value="{{ $currencyCode }}" class="kt-checkbox mt-1" @checked(in_array($currencyCode, $selectedCurrencies, true))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <label class="kt-form-label">Aktif Ödeme Yöntemleri</label>
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($paymentMethodOptions as $methodKey => $label)
                                <label class="flex items-start gap-3 rounded-2xl border border-border bg-muted/20 px-4 py-4 text-sm text-muted-foreground">
                                    <input type="checkbox" name="allowed_payment_methods[]" value="{{ $methodKey }}" class="kt-checkbox mt-1" @checked(in_array($methodKey, $selectedPaymentMethods, true))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="grid gap-3">
                            <label class="kt-form-label">Taksit Kullanımı</label>
                            <label class="flex items-start gap-3 rounded-2xl border border-border bg-muted/20 px-4 py-4 text-sm text-muted-foreground">
                                <input type="hidden" name="installment_enabled" value="0">
                                <input type="checkbox" name="installment_enabled" value="1" class="kt-checkbox mt-1" @checked($installmentEnabled)>
                                <span>Bu entegrasyon için taksitli ödeme seçeneği aktif olsun.</span>
                            </label>
                        </div>

                        <div class="grid gap-3">
                            <label class="kt-form-label">Taksit Seçenekleri</label>
                            <div class="grid gap-3 grid-cols-2 md:grid-cols-3">
                                @foreach([2, 3, 6, 9, 12] as $installment)
                                    <label class="flex items-center gap-3 rounded-2xl border border-border bg-muted/20 px-4 py-4 text-sm text-muted-foreground">
                                        <input type="checkbox" name="installment_options[]" value="{{ $installment }}" class="kt-checkbox" @checked(in_array($installment, $selectedInstallments, true))>
                                        <span>{{ $installment }} Taksit</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('installment_options')
                                <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Dönüş URL ve Güvenlik Sınırları</h3>
                        <div class="text-sm text-muted-foreground">Canlı ortamda mümkün olduğunca HTTPS URL ve sağlayıcı IP kısıtı kullan.</div>
                    </div>
                </div>

                <div class="kt-card-content grid gap-4 p-6 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Başarılı Dönüş URL</label>
                        <input name="success_url" type="url" class="kt-input @error('success_url') kt-input-invalid @enderror" value="{{ old('success_url', $paymentIntegration?->success_url) }}" placeholder="https://alanadi.com/odeme/basarili">
                        @error('success_url')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Başarısız / İptal URL</label>
                        <input name="cancel_url" type="url" class="kt-input @error('cancel_url') kt-input-invalid @enderror" value="{{ old('cancel_url', $paymentIntegration?->cancel_url) }}" placeholder="https://alanadi.com/odeme/iptal">
                        @error('cancel_url')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2 lg:col-span-2">
                        <label class="kt-form-label">Webhook IP Beyaz Liste</label>
                        <textarea name="webhook_ip_whitelist" rows="3" class="kt-textarea @error('webhook_ip_whitelist') kt-input-invalid @enderror" placeholder="Örn. 1.2.3.4, 5.6.7.8">{{ old('webhook_ip_whitelist', $paymentIntegration?->webhook_ip_whitelist) }}</textarea>
                        <div class="text-xs text-muted-foreground">Virgül ile ayırarak sağlayıcının izinli IP bloklarını not düşebilirsin.</div>
                        @error('webhook_ip_whitelist')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2 lg:col-span-2">
                        <label class="kt-form-label">Operasyon Notları</label>
                        <textarea name="notes" rows="4" class="kt-textarea @error('notes') kt-input-invalid @enderror" placeholder="Komisyon, canlı geçiş tarihi, onay notları vb.">{{ old('notes', $paymentIntegration?->notes) }}</textarea>
                        @error('notes')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 self-start xl:sticky xl:top-6">
            <div class="rounded-[28px] app-surface-card p-5">
                <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">Güvenlik Özeti</div>
                <div class="mt-4 grid gap-3 text-sm text-muted-foreground">
                    <div class="rounded-2xl border border-border bg-background/80 px-4 py-4 text-foreground">
                        Secret alanlar veritabanında şifreli tutulur ve audit log içinde redakte edilir.
                    </div>
                    <div class="rounded-2xl border border-border bg-background/80 px-4 py-4 text-foreground">
                        Sağlayıcı tipi oluşturulduktan sonra değiştirilemez; farklı yapı için yeni kayıt açmalısın.
                    </div>
                    <div class="rounded-2xl border border-border bg-background/80 px-4 py-4 text-foreground">
                        Canlı moda geçmeden önce dönüş URL’lerinin HTTPS olduğundan ve gereksiz yöntemlerin kapalı olduğundan emin ol.
                    </div>
                </div>
            </div>

            @if($isEdit)
                @php($health = $paymentIntegration->healthBadge())
                <div class="rounded-[28px] app-surface-card p-5">
                    <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">Kayıt Sağlığı</div>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="{{ $health['class'] }}">{{ $health['label'] }}</span>
                        <span class="{{ $paymentIntegration->environmentBadgeClass() }}">{{ $paymentIntegration->environmentLabel() }}</span>
                    </div>
                    <div class="mt-4 grid gap-2 text-sm text-muted-foreground">
                        <div>Son secret rotasyonu: {{ optional($paymentIntegration->credentials_rotated_at)->format('d.m.Y H:i') ?: 'Henüz yok' }}</div>
                        <div>Varsayılan kayıt: {{ $paymentIntegration->is_default ? 'Evet' : 'Hayır' }}</div>
                        <div>Aktif durum: {{ $paymentIntegration->is_active ? 'Aktif' : 'Pasif' }}</div>
                    </div>
                </div>
            @endif

            <button type="submit" class="kt-btn kt-btn-primary w-full">
                {{ $isEdit ? 'Değişiklikleri Kaydet' : 'Entegrasyonu Kaydet' }}
            </button>
        </div>
    </div>
</form>
