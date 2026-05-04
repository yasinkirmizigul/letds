@php
    $isTemplate = $isTemplate ?? false;
    $rowIndex = $index;
    $productId = $item['product_id'] ?? null;
    $fulfillmentValue = $item['fulfillment_status'] ?? \App\Models\Admin\Ecommerce\EcommerceOrder::FULFILLMENT_UNFULFILLED;
@endphp

<div class="rounded-2xl border border-border bg-background p-4" data-order-item-row>
    <div class="grid gap-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="grid flex-1 gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,.85fr)]">
                <div class="grid gap-2">
                    <label class="kt-form-label">Katalog Ürünü</label>
                    <select
                        name="items[{{ $rowIndex }}][product_id]"
                        class="kt-select"
                        data-kt-select="true"
                        data-order-item-product
                    >
                        <option value="">Manuel satır</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((string) $productId === (string) $product->id)>
                                {{ $product->title }}{{ $product->sku ? ' - ' . $product->sku : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label">Satır Durumu</label>
                    <select name="items[{{ $rowIndex }}][fulfillment_status]" class="kt-select" data-kt-select="true">
                        @foreach($fulfillmentStatusOptions as $key => $option)
                            <option value="{{ $key }}" @selected($fulfillmentValue === $key)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="button" class="kt-btn kt-btn-icon kt-btn-light shrink-0" data-order-remove-item title="Satırı kaldır">
                <i class="ki-filled ki-trash"></i>
            </button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="grid gap-2 md:col-span-2">
                <label class="kt-form-label">Ürün / Hizmet Adı</label>
                <input name="items[{{ $rowIndex }}][product_title]" class="kt-input" value="{{ $item['product_title'] ?? '' }}" data-order-item-title placeholder="Ürün adı veya manuel hizmet">
            </div>
            <div class="grid gap-2">
                <label class="kt-form-label">SKU</label>
                <input name="items[{{ $rowIndex }}][sku]" class="kt-input" value="{{ $item['sku'] ?? '' }}" data-order-item-sku>
            </div>
            <div class="grid gap-2">
                <label class="kt-form-label">Marka</label>
                <input name="items[{{ $rowIndex }}][brand]" class="kt-input" value="{{ $item['brand'] ?? '' }}" data-order-item-brand>
            </div>
        </div>

        <input type="hidden" name="items[{{ $rowIndex }}][barcode]" value="{{ $item['barcode'] ?? '' }}" data-order-item-barcode>

        <div class="grid gap-4 md:grid-cols-5">
            <div class="grid gap-2">
                <label class="kt-form-label">Adet</label>
                <input name="items[{{ $rowIndex }}][quantity]" type="number" min="0.001" step="0.001" class="kt-input" value="{{ $numberValue($item['quantity'] ?? 1, 3) }}" data-order-item-quantity>
            </div>
            <div class="grid gap-2">
                <label class="kt-form-label">Birim Fiyat</label>
                <input name="items[{{ $rowIndex }}][unit_price]" type="number" min="0" step="0.01" class="kt-input" value="{{ $numberValue($item['unit_price'] ?? 0) }}" data-order-item-unit-price>
            </div>
            <div class="grid gap-2">
                <label class="kt-form-label">Satır İndirimi</label>
                <input name="items[{{ $rowIndex }}][discount_total]" type="number" min="0" step="0.01" class="kt-input" value="{{ $numberValue($item['discount_total'] ?? 0) }}" data-order-item-discount>
            </div>
            <div class="grid gap-2">
                <label class="kt-form-label">KDV %</label>
                <input name="items[{{ $rowIndex }}][tax_rate]" type="number" min="0" max="100" step="0.01" class="kt-input" value="{{ $numberValue($item['tax_rate'] ?? 0) }}" data-order-item-tax-rate>
            </div>
            <div class="grid gap-2">
                <label class="kt-form-label">Satır Toplamı</label>
                <div class="kt-input bg-muted/20 text-foreground" data-order-item-total>0,00</div>
            </div>
        </div>

        <details class="group">
            <summary class="cursor-pointer text-sm font-medium text-primary">Satır ek alanları</summary>
            <div class="mt-3 grid gap-2">
                <label class="kt-form-label">Satır Ek Alanları (JSON)</label>
                <textarea name="items[{{ $rowIndex }}][custom_fields_json]" rows="3" class="kt-textarea font-mono text-xs" placeholder='{"seri_no":"ABC123"}'>{{ $item['custom_fields_json'] ?? '' }}</textarea>
            </div>
        </details>
    </div>
</div>
