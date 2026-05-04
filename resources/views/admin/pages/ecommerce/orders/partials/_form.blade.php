@php
    use App\Models\Admin\Ecommerce\EcommerceOrder;

    $isEdit = filled($order?->id);
    $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
    $numberValue = function ($value, int $precision = 2): string {
        if ($value === null || $value === '') {
            return '';
        }

        $formatted = number_format((float) $value, $precision, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    };
    $dateValue = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i') : '';
    $selectedItems = old('items');
    if (!is_array($selectedItems)) {
        $selectedItems = collect($items ?? [])->map(fn ($item) => [
            'product_id' => $item->product_id ?? null,
            'product_title' => $item->product_title ?? '',
            'sku' => $item->sku ?? '',
            'barcode' => $item->barcode ?? '',
            'brand' => $item->brand ?? '',
            'quantity' => $item->quantity ?? 1,
            'unit_price' => $item->unit_price ?? 0,
            'discount_total' => $item->discount_total ?? 0,
            'tax_rate' => $item->tax_rate ?? 0,
            'fulfillment_status' => $item->fulfillment_status ?? EcommerceOrder::FULFILLMENT_UNFULFILLED,
            'custom_fields_json' => filled($item->custom_fields ?? null) ? json_encode($item->custom_fields, $jsonFlags) : '',
        ])->values()->all();
    }

    if (count($selectedItems) === 0) {
        $selectedItems = [[
            'product_id' => null,
            'product_title' => '',
            'sku' => '',
            'barcode' => '',
            'brand' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'fulfillment_status' => EcommerceOrder::FULFILLMENT_UNFULFILLED,
            'custom_fields_json' => '',
        ]];
    }

    $billingAddress = old('billing_address', $order->billing_address ?? []);
    $shippingAddress = old('shipping_address', $order->shipping_address ?? []);
    $customFieldsJson = old(
        'custom_fields_json',
        filled($order->custom_fields ?? null) ? json_encode($order->custom_fields, $jsonFlags) : ''
    );
    $orderedAt = old('ordered_at', $dateValue($order->ordered_at ?? null));
    $paidAt = old('paid_at', $dateValue($order->paid_at ?? null));
    $shippedAt = old('shipped_at', $dateValue($order->shipped_at ?? null));
    $deliveredAt = old('delivered_at', $dateValue($order->delivered_at ?? null));
    $cancelledAt = old('cancelled_at', $dateValue($order->cancelled_at ?? null));
@endphp

<div
    class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.55fr)_420px]"
    data-product-options='@json($productOptionsJson ?? [])'
>
    <div class="grid gap-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Sipariş Kimliği</h3>
                    <div class="text-sm text-muted-foreground">Kaynak kanal, müşteri ve ödeme bağlantısını netleştirin.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-5">
                <div class="grid gap-5 md:grid-cols-3">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="order_number">Sipariş No</label>
                        <input id="order_number" name="order_number" class="kt-input @error('order_number') kt-input-invalid @enderror" value="{{ old('order_number', $order->order_number ?? '') }}" placeholder="Otomatik oluşur">
                        @error('order_number')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="reference_code">Dış Referans</label>
                        <input id="reference_code" name="reference_code" class="kt-input @error('reference_code') kt-input-invalid @enderror" value="{{ old('reference_code', $order->reference_code ?? '') }}" placeholder="Pazaryeri, ERP veya kampanya kodu">
                        @error('reference_code')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="channel">Kanal</label>
                        <select id="channel" name="channel" class="kt-select @error('channel') kt-input-invalid @enderror" data-kt-select="true">
                            @foreach($channelOptions as $key => $label)
                                <option value="{{ $key }}" @selected(old('channel', $order->channel ?? 'admin') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('channel')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="member_id">Üye Bağlantısı</label>
                        <select id="member_id" name="member_id" class="kt-select @error('member_id') kt-input-invalid @enderror" data-kt-select="true" data-kt-select-placeholder="Üye seç">
                            <option value="">Manuel müşteri</option>
                            @foreach($members as $member)
                                <option
                                    value="{{ $member->id }}"
                                    @selected((string) old('member_id', $order->member_id ?? '') === (string) $member->id)
                                    data-member-name="{{ trim($member->name . ' ' . $member->surname) }}"
                                    data-member-email="{{ $member->email }}"
                                    data-member-phone="{{ $member->phone }}"
                                >
                                    {{ trim($member->name . ' ' . $member->surname) }} - {{ $member->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('member_id')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="payment_integration_id">Ödeme Entegrasyonu</label>
                        <select id="payment_integration_id" name="payment_integration_id" class="kt-select @error('payment_integration_id') kt-input-invalid @enderror" data-kt-select="true">
                            <option value="">Manuel / Entegrasyonsuz</option>
                            @foreach($paymentIntegrations as $integration)
                                <option value="{{ $integration->id }}" @selected((string) old('payment_integration_id', $order->payment_integration_id ?? '') === (string) $integration->id)>
                                    {{ $integration->title }} {{ $integration->is_default ? '(Varsayılan)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_integration_id')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="payment_method">Ödeme Yöntemi</label>
                        <input id="payment_method" name="payment_method" class="kt-input @error('payment_method') kt-input-invalid @enderror" value="{{ old('payment_method', $order->payment_method ?? '') }}" placeholder="Kredi kartı, havale, kapıda ödeme">
                        @error('payment_method')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="customer_name">Müşteri Adı</label>
                        <input id="customer_name" name="customer_name" class="kt-input @error('customer_name') kt-input-invalid @enderror" value="{{ old('customer_name', $order->customer_name ?? '') }}" required>
                        @error('customer_name')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="customer_email">E-posta</label>
                        <input id="customer_email" name="customer_email" type="email" class="kt-input @error('customer_email') kt-input-invalid @enderror" value="{{ old('customer_email', $order->customer_email ?? '') }}">
                        @error('customer_email')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="customer_phone">Telefon</label>
                        <input id="customer_phone" name="customer_phone" class="kt-input @error('customer_phone') kt-input-invalid @enderror" value="{{ old('customer_phone', $order->customer_phone ?? '') }}">
                        @error('customer_phone')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="customer_company">Firma</label>
                        <input id="customer_company" name="customer_company" class="kt-input @error('customer_company') kt-input-invalid @enderror" value="{{ old('customer_company', $order->customer_company ?? '') }}">
                        @error('customer_company')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="customer_tax_number">Vergi No / TCKN</label>
                        <input id="customer_tax_number" name="customer_tax_number" class="kt-input @error('customer_tax_number') kt-input-invalid @enderror" value="{{ old('customer_tax_number', $order->customer_tax_number ?? '') }}">
                        @error('customer_tax_number')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="customer_tax_office">Vergi Dairesi</label>
                        <input id="customer_tax_office" name="customer_tax_office" class="kt-input @error('customer_tax_office') kt-input-invalid @enderror" value="{{ old('customer_tax_office', $order->customer_tax_office ?? '') }}">
                        @error('customer_tax_office')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5 flex-wrap gap-3">
                <div>
                    <h3 class="kt-card-title">Ürün Satırları</h3>
                    <div class="text-sm text-muted-foreground">Katalog ürünü seçebilir veya manuel satır ekleyebilirsiniz.</div>
                </div>
                <button type="button" class="kt-btn kt-btn-light" data-order-add-item>
                    <i class="ki-filled ki-plus"></i>
                    Satır Ekle
                </button>
            </div>
            <div class="kt-card-content p-6 grid gap-4" data-order-items>
                @error('items')<div class="kt-alert kt-alert-danger mb-5">{{ $message }}</div>@enderror

                @foreach($selectedItems as $index => $item)
                    @include('admin.pages.ecommerce.orders.partials._item-row', [
                        'index' => $index,
                        'item' => $item,
                        'products' => $products,
                        'fulfillmentStatusOptions' => $fulfillmentStatusOptions,
                        'numberValue' => $numberValue,
                    ])
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="kt-card overflow-hidden">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Fatura Adresi</h3>
                        <div class="text-sm text-muted-foreground">Fatura kesimi ve cari aktarımı için temel alanlar.</div>
                    </div>
                </div>
                <div class="kt-card-content p-6 grid gap-4">
                    @foreach([
                        'name' => 'Ad Soyad / Ünvan',
                        'phone' => 'Telefon',
                        'line1' => 'Adres Satırı 1',
                        'line2' => 'Adres Satırı 2',
                        'district' => 'İlçe',
                        'city' => 'İl',
                        'postal_code' => 'Posta Kodu',
                        'country' => 'Ülke',
                    ] as $field => $label)
                        <div class="grid gap-2">
                            <label class="kt-form-label" for="billing_{{ $field }}">{{ $label }}</label>
                            <input id="billing_{{ $field }}" name="billing_address[{{ $field }}]" class="kt-input" value="{{ $billingAddress[$field] ?? ($field === 'country' ? 'TR' : '') }}">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="kt-card overflow-hidden">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Teslimat Adresi</h3>
                        <div class="text-sm text-muted-foreground">Kargo etiketi ve teslimat takibi için adres bilgileri.</div>
                    </div>
                </div>
                <div class="kt-card-content p-6 grid gap-4">
                    @foreach([
                        'name' => 'Alıcı',
                        'phone' => 'Telefon',
                        'line1' => 'Adres Satırı 1',
                        'line2' => 'Adres Satırı 2',
                        'district' => 'İlçe',
                        'city' => 'İl',
                        'postal_code' => 'Posta Kodu',
                        'country' => 'Ülke',
                    ] as $field => $label)
                        <div class="grid gap-2">
                            <label class="kt-form-label" for="shipping_{{ $field }}">{{ $label }}</label>
                            <input id="shipping_{{ $field }}" name="shipping_address[{{ $field }}]" class="kt-input" value="{{ $shippingAddress[$field] ?? ($field === 'country' ? 'TR' : '') }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Notlar ve Ek Alanlar</h3>
                    <div class="text-sm text-muted-foreground">Müşteri notu, iç operasyon notu ve modül genişletmeleri için özel alanlar.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="customer_note">Müşteri Notu</label>
                        <textarea id="customer_note" name="customer_note" rows="4" class="kt-textarea @error('customer_note') kt-input-invalid @enderror">{{ old('customer_note', $order->customer_note ?? '') }}</textarea>
                        @error('customer_note')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="internal_note">İç Not</label>
                        <textarea id="internal_note" name="internal_note" rows="4" class="kt-textarea @error('internal_note') kt-input-invalid @enderror">{{ old('internal_note', $order->internal_note ?? '') }}</textarea>
                        @error('internal_note')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label" for="custom_fields_json">Ek Alanlar (JSON formatı)</label>
                    <textarea id="custom_fields_json" name="custom_fields_json" rows="5" class="kt-textarea font-mono text-xs @error('custom_fields_json') kt-input-invalid @enderror" placeholder='{"crm_id":"123","teslimat_penceresi":"10:00-14:00"}'>{{ $customFieldsJson }}</textarea>
                    @error('custom_fields_json')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 self-start xl:sticky xl:top-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Operasyon Durumu</h3>
                    <div class="text-sm text-muted-foreground">Sipariş, ödeme ve teslimat akışı.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-4">
                <div class="grid gap-2">
                    <label class="kt-form-label" for="status">Sipariş Durumu</label>
                    <select id="status" name="status" class="kt-select @error('status') kt-input-invalid @enderror" data-kt-select="true">
                        @foreach($statusOptions as $key => $option)
                            <option value="{{ $key }}" @selected(old('status', $order->status ?? EcommerceOrder::STATUS_DRAFT) === $key)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label" for="payment_status">Ödeme Durumu</label>
                    <select id="payment_status" name="payment_status" class="kt-select @error('payment_status') kt-input-invalid @enderror" data-kt-select="true">
                        @foreach($paymentStatusOptions as $key => $option)
                            <option value="{{ $key }}" @selected(old('payment_status', $order->payment_status ?? EcommerceOrder::PAYMENT_UNPAID) === $key)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('payment_status')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label" for="fulfillment_status">Teslimat Durumu</label>
                    <select id="fulfillment_status" name="fulfillment_status" class="kt-select @error('fulfillment_status') kt-input-invalid @enderror" data-kt-select="true">
                        @foreach($fulfillmentStatusOptions as $key => $option)
                            <option value="{{ $key }}" @selected(old('fulfillment_status', $order->fulfillment_status ?? EcommerceOrder::FULFILLMENT_UNFULFILLED) === $key)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('fulfillment_status')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label" for="status_note">Durum Notu</label>
                    <textarea id="status_note" name="status_note" rows="3" class="kt-textarea @error('status_note') kt-input-invalid @enderror" placeholder="Durum değişikliğini açıklayan kısa not">{{ old('status_note') }}</textarea>
                    @error('status_note')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Tutar Özeti</h3>
                    <div class="text-sm text-muted-foreground">Toplamlar satırlardan otomatik hesaplanır.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-4">
                <div class="grid gap-2">
                    <label class="kt-form-label" for="currency">Para Birimi</label>
                    <input id="currency" name="currency" maxlength="3" class="kt-input uppercase @error('currency') kt-input-invalid @enderror" value="{{ old('currency', $order->currency ?? 'TRY') }}" data-order-currency>
                    @error('currency')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label" for="order_discount_total">Sipariş İndirimi</label>
                    <input id="order_discount_total" name="order_discount_total" type="number" min="0" step="0.01" class="kt-input @error('order_discount_total') kt-input-invalid @enderror" value="{{ old('order_discount_total', $numberValue($order->order_discount_total ?? 0)) }}" data-order-discount>
                    @error('order_discount_total')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label" for="shipping_total">Kargo / Hizmet Bedeli</label>
                    <input id="shipping_total" name="shipping_total" type="number" min="0" step="0.01" class="kt-input @error('shipping_total') kt-input-invalid @enderror" value="{{ old('shipping_total', $numberValue($order->shipping_total ?? 0)) }}" data-order-shipping>
                    @error('shipping_total')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="rounded-2xl border border-border bg-muted/20 p-4">
                    <div class="grid gap-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">Ara Toplam</span>
                            <b data-order-summary="subtotal">{{ number_format((float) ($order->subtotal ?? 0), 2, ',', '.') }}</b>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">İndirim</span>
                            <b data-order-summary="discount">{{ number_format((float) ($order->discount_total ?? 0), 2, ',', '.') }}</b>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">KDV</span>
                            <b data-order-summary="tax">{{ number_format((float) ($order->tax_total ?? 0), 2, ',', '.') }}</b>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-border pt-3 text-base">
                            <span class="font-semibold text-foreground">Genel Toplam</span>
                            <b class="text-primary" data-order-summary="grand">{{ number_format((float) ($order->grand_total ?? 0), 2, ',', '.') }}</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Tarihler ve Kargo</h3>
                    <div class="text-sm text-muted-foreground">Metronic uyumlu tarih alanları ve kargo bilgisi.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-4">
                @foreach([
                    'ordered_at' => ['Sipariş Tarihi', $orderedAt],
                    'paid_at' => ['Ödeme Tarihi', $paidAt],
                    'shipped_at' => ['Kargo Tarihi', $shippedAt],
                    'delivered_at' => ['Teslim Tarihi', $deliveredAt],
                    'cancelled_at' => ['İptal Tarihi', $cancelledAt],
                ] as $field => [$label, $value])
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="{{ $field }}">{{ $label }}</label>
                        <input
                            id="{{ $field }}"
                            name="{{ $field }}"
                            type="text"
                            class="kt-input @error($field) kt-input-invalid @enderror"
                            value="{{ $value }}"
                            data-app-date-picker="true"
                            data-app-date-mode="datetime"
                            data-initial-value="{{ $value }}"
                            placeholder="gg.aa.yyyy ss:dd"
                        >
                        @error($field)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                @endforeach

                <div class="grid gap-2">
                    <label class="kt-form-label" for="shipping_carrier">Kargo Firması</label>
                    <input id="shipping_carrier" name="shipping_carrier" class="kt-input @error('shipping_carrier') kt-input-invalid @enderror" value="{{ old('shipping_carrier', $order->shipping_carrier ?? '') }}">
                    @error('shipping_carrier')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label" for="tracking_number">Takip No</label>
                    <input id="tracking_number" name="tracking_number" class="kt-input @error('tracking_number') kt-input-invalid @enderror" value="{{ old('tracking_number', $order->tracking_number ?? '') }}">
                    @error('tracking_number')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label" for="tracking_url">Takip Bağlantısı</label>
                    <input id="tracking_url" name="tracking_url" class="kt-input @error('tracking_url') kt-input-invalid @enderror" value="{{ old('tracking_url', $order->tracking_url ?? '') }}">
                    @error('tracking_url')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            @if($isEdit)
                <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="kt-btn kt-btn-light">Vazgeç</a>
            @else
                <a href="{{ route('admin.ecommerce.orders.index') }}" class="kt-btn kt-btn-light">Vazgeç</a>
            @endif
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check"></i>
                Siparişi Kaydet
            </button>
        </div>
    </div>
</div>

<template data-order-item-template>
    @include('admin.pages.ecommerce.orders.partials._item-row', [
        'index' => '__INDEX__',
        'item' => [
            'product_id' => null,
            'product_title' => '',
            'sku' => '',
            'barcode' => '',
            'brand' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'fulfillment_status' => EcommerceOrder::FULFILLMENT_UNFULFILLED,
            'custom_fields_json' => '',
        ],
        'products' => $products,
        'fulfillmentStatusOptions' => $fulfillmentStatusOptions,
        'numberValue' => $numberValue,
        'isTemplate' => true,
    ])
</template>
