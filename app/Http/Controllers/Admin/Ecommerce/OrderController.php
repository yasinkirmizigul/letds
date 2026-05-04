<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Admin\Ecommerce\EcommerceOrder;
use App\Models\Admin\Ecommerce\EcommerceOrderStatusHistory;
use App\Models\Admin\Ecommerce\EcommerceOrderTransaction;
use App\Models\Admin\Ecommerce\EcommerceShipment;
use App\Models\Admin\Product\Product;
use App\Models\Member;
use App\Models\Site\PaymentIntegration;
use App\Support\Audit\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status', 'all');
        $paymentStatus = (string) $request->string('payment_status', 'all');
        $fulfillmentStatus = (string) $request->string('fulfillment_status', 'all');
        $channel = (string) $request->string('channel', 'all');
        $perPage = max(10, min(100, (int) $request->input('per_page', 25)));

        $orders = EcommerceOrder::query()
            ->with(['paymentIntegration:id,title,provider', 'items:id,order_id,product_title,quantity,total'])
            ->search($search)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($paymentStatus !== 'all', fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($fulfillmentStatus !== 'all', fn ($query) => $query->where('fulfillment_status', $fulfillmentStatus))
            ->when($channel !== 'all', fn ($query) => $query->where('channel', $channel))
            ->latest('ordered_at')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.pages.ecommerce.orders.index', [
            'pageTitle' => 'Sipariş Yönetimi',
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'paymentStatus' => $paymentStatus,
            'fulfillmentStatus' => $fulfillmentStatus,
            'channel' => $channel,
            'statusOptions' => EcommerceOrder::statusOptions(),
            'paymentStatusOptions' => EcommerceOrder::paymentStatusOptions(),
            'fulfillmentStatusOptions' => EcommerceOrder::fulfillmentStatusOptions(),
            'channelOptions' => $this->channelOptions(),
            'stats' => $this->stats(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.ecommerce.orders.create', array_merge($this->formPayload(), [
            'pageTitle' => 'Sipariş Oluştur',
            'order' => new EcommerceOrder([
                'status' => EcommerceOrder::STATUS_DRAFT,
                'payment_status' => EcommerceOrder::PAYMENT_UNPAID,
                'fulfillment_status' => EcommerceOrder::FULFILLMENT_UNFULFILLED,
                'channel' => 'admin',
                'currency' => 'TRY',
                'ordered_at' => now(),
            ]),
            'items' => collect(),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOrder($request);

        $order = DB::transaction(function () use ($validated) {
            $order = EcommerceOrder::create($this->orderPayload($validated));
            $this->syncItems($order, $validated);
            $this->recordHistory($order, null, null, null, 'Sipariş oluşturuldu.');

            return $order;
        });

        AuditEvent::log('ecommerce_orders.create', [
            'order_id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
        ]);

        return redirect()
            ->route('admin.ecommerce.orders.show', $order)
            ->with('success', 'Sipariş oluşturuldu.');
    }

    public function show(EcommerceOrder $order): View
    {
        $order->load([
            'member:id,name,surname,email,phone',
            'paymentIntegration:id,title,provider,environment',
            'items.product:id,title,sku,stock,price,sale_price,currency',
            'transactions.paymentIntegration:id,title,provider',
            'shipments',
            'histories.user:id,name,email',
        ]);

        return view('admin.pages.ecommerce.orders.show', array_merge($this->formPayload(), [
            'pageTitle' => 'Sipariş Detayı',
            'order' => $order,
            'transactionTypeOptions' => EcommerceOrderTransaction::typeOptions(),
            'transactionStatusOptions' => EcommerceOrderTransaction::statusOptions(),
            'shipmentStatusOptions' => EcommerceShipment::statusOptions(),
        ]));
    }

    public function edit(EcommerceOrder $order): View
    {
        $order->load(['items.product', 'member', 'paymentIntegration']);

        return view('admin.pages.ecommerce.orders.edit', array_merge($this->formPayload(), [
            'pageTitle' => 'Sipariş Düzenle',
            'order' => $order,
            'items' => $order->items,
        ]));
    }

    public function update(Request $request, EcommerceOrder $order): RedirectResponse
    {
        $validated = $this->validateOrder($request, $order);

        DB::transaction(function () use ($order, $validated) {
            $order = EcommerceOrder::query()->lockForUpdate()->findOrFail($order->id);
            $beforeStatus = (string) $order->status;
            $beforePayment = (string) $order->payment_status;
            $beforeFulfillment = (string) $order->fulfillment_status;

            $order->update($this->orderPayload($validated, $order));
            $this->syncItems($order, $validated);

            if (
                $beforeStatus !== (string) $order->status
                || $beforePayment !== (string) $order->payment_status
                || $beforeFulfillment !== (string) $order->fulfillment_status
            ) {
                $this->recordHistory($order, $beforeStatus, $beforePayment, $beforeFulfillment, $validated['status_note'] ?? null);
            }
        });

        AuditEvent::log('ecommerce_orders.update', [
            'order_id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
        ]);

        return redirect()
            ->route('admin.ecommerce.orders.show', $order)
            ->with('success', 'Sipariş güncellendi.');
    }

    public function destroy(EcommerceOrder $order): RedirectResponse
    {
        $order->delete();

        AuditEvent::log('ecommerce_orders.delete', [
            'order_id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
        ]);

        return redirect()
            ->route('admin.ecommerce.orders.index')
            ->with('success', 'Sipariş arşive alındı.');
    }

    public function storeTransaction(Request $request, EcommerceOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(EcommerceOrderTransaction::typeOptions()))],
            'status' => ['required', Rule::in(array_keys(EcommerceOrderTransaction::statusOptions()))],
            'payment_integration_id' => ['nullable', 'integer', 'exists:payment_integrations,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'gateway_transaction_id' => ['nullable', 'string', 'max:190'],
            'gateway_reference' => ['nullable', 'string', 'max:190'],
            'processed_at' => ['nullable', 'string', 'max:40'],
            'payload_json' => ['nullable', 'string', 'max:8000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $payload = $this->decodeJsonField($validated['payload_json'] ?? null, 'payload_json');

        DB::transaction(function () use ($order, $validated, $payload) {
            $beforePayment = (string) $order->payment_status;

            $order->transactions()->create([
                'payment_integration_id' => $validated['payment_integration_id'] ?? $order->payment_integration_id,
                'type' => (string) $validated['type'],
                'status' => (string) $validated['status'],
                'amount' => $this->decimal($validated['amount'] ?? 0),
                'currency' => strtoupper((string) $validated['currency']),
                'gateway_transaction_id' => $this->nullableString($validated['gateway_transaction_id'] ?? null),
                'gateway_reference' => $this->nullableString($validated['gateway_reference'] ?? null),
                'processed_at' => $this->parseDateTime($validated['processed_at'] ?? null, 'processed_at'),
                'payload' => $payload,
                'notes' => $this->nullableString($validated['notes'] ?? null),
            ]);

            $this->refreshPaymentTotals($order);

            if ($beforePayment !== (string) $order->payment_status) {
                $this->recordHistory($order, (string) $order->status, $beforePayment, (string) $order->fulfillment_status, 'Ödeme hareketi işlendi.');
            }
        });

        AuditEvent::log('ecommerce_orders.transaction.create', [
            'order_id' => (int) $order->id,
            'type' => (string) $validated['type'],
        ]);

        return redirect()
            ->route('admin.ecommerce.orders.show', $order)
            ->with('success', 'Ödeme hareketi eklendi.');
    }

    public function storeShipment(Request $request, EcommerceOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(EcommerceShipment::statusOptions()))],
            'carrier' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
            'package_count' => ['nullable', 'integer', 'min:1', 'max:999'],
            'shipped_at' => ['nullable', 'string', 'max:40'],
            'delivered_at' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($order, $validated) {
            $beforeStatus = (string) $order->status;
            $beforeFulfillment = (string) $order->fulfillment_status;

            $shipment = $order->shipments()->create([
                'status' => (string) $validated['status'],
                'carrier' => $this->nullableString($validated['carrier'] ?? null),
                'tracking_number' => $this->nullableString($validated['tracking_number'] ?? null),
                'tracking_url' => $this->nullableString($validated['tracking_url'] ?? null),
                'package_count' => (int) ($validated['package_count'] ?? 1),
                'address' => $order->shipping_address,
                'shipped_at' => $this->parseDateTime($validated['shipped_at'] ?? null, 'shipped_at'),
                'delivered_at' => $this->parseDateTime($validated['delivered_at'] ?? null, 'delivered_at'),
                'notes' => $this->nullableString($validated['notes'] ?? null),
            ]);

            $this->applyShipmentState($order, $shipment);

            if ($beforeStatus !== (string) $order->status || $beforeFulfillment !== (string) $order->fulfillment_status) {
                $this->recordHistory($order, $beforeStatus, (string) $order->payment_status, $beforeFulfillment, 'Kargo hareketi işlendi.');
            }
        });

        AuditEvent::log('ecommerce_orders.shipment.create', [
            'order_id' => (int) $order->id,
            'tracking_number' => $validated['tracking_number'] ?? null,
        ]);

        return redirect()
            ->route('admin.ecommerce.orders.show', $order)
            ->with('success', 'Kargo kaydı eklendi.');
    }

    private function validateOrder(Request $request, ?EcommerceOrder $order = null): array
    {
        $rules = [
            'order_number' => ['nullable', 'string', 'max:40', Rule::unique('ecommerce_orders', 'order_number')->ignore($order?->id)],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'payment_integration_id' => ['nullable', 'integer', 'exists:payment_integrations,id'],
            'channel' => ['required', Rule::in(array_keys($this->channelOptions()))],
            'reference_code' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(array_keys(EcommerceOrder::statusOptions()))],
            'payment_status' => ['required', Rule::in(array_keys(EcommerceOrder::paymentStatusOptions()))],
            'fulfillment_status' => ['required', Rule::in(array_keys(EcommerceOrder::fulfillmentStatusOptions()))],
            'status_note' => ['nullable', 'string', 'max:1000'],
            'customer_name' => ['required', 'string', 'max:190'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'customer_company' => ['nullable', 'string', 'max:190'],
            'customer_tax_number' => ['nullable', 'string', 'max:80'],
            'customer_tax_office' => ['nullable', 'string', 'max:120'],
            'currency' => ['required', 'string', 'size:3'],
            'order_discount_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'shipping_carrier' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
            'ordered_at' => ['nullable', 'string', 'max:40'],
            'paid_at' => ['nullable', 'string', 'max:40'],
            'shipped_at' => ['nullable', 'string', 'max:40'],
            'delivered_at' => ['nullable', 'string', 'max:40'],
            'cancelled_at' => ['nullable', 'string', 'max:40'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.*' => ['nullable', 'string', 'max:255'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.*' => ['nullable', 'string', 'max:255'],
            'customer_note' => ['nullable', 'string', 'max:5000'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
            'custom_fields_json' => ['nullable', 'string', 'max:10000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_title' => ['nullable', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:120'],
            'items.*.barcode' => ['nullable', 'string', 'max:120'],
            'items.*.brand' => ['nullable', 'string', 'max:160'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.fulfillment_status' => ['nullable', Rule::in(array_keys(EcommerceOrder::fulfillmentStatusOptions()))],
            'items.*.custom_fields_json' => ['nullable', 'string', 'max:4000'],
        ];

        $validated = $request->validate($rules);
        $validated['custom_fields'] = $this->decodeJsonField($validated['custom_fields_json'] ?? null, 'custom_fields_json');
        unset($validated['custom_fields_json']);

        $items = collect($validated['items'] ?? [])
            ->map(function (array $item) {
                $item['custom_fields'] = $this->decodeJsonField($item['custom_fields_json'] ?? null, 'items.*.custom_fields_json');
                unset($item['custom_fields_json']);

                return $item;
            })
            ->filter(fn (array $item) => filled($item['product_id'] ?? null) || filled($item['product_title'] ?? null))
            ->values()
            ->all();

        if (count($items) === 0) {
            throw ValidationException::withMessages([
                'items' => 'Sipariş için en az bir ürün veya manuel satır eklemelisiniz.',
            ]);
        }

        $validated['items'] = $items;
        $validated['currency'] = strtoupper((string) $validated['currency']);

        return $validated;
    }

    private function orderPayload(array $validated, ?EcommerceOrder $order = null): array
    {
        return [
            'order_number' => filled($validated['order_number'] ?? null)
                ? strtoupper((string) $validated['order_number'])
                : ($order?->order_number ?? null),
            'member_id' => $validated['member_id'] ?? null,
            'payment_integration_id' => $validated['payment_integration_id'] ?? null,
            'channel' => (string) $validated['channel'],
            'reference_code' => $this->nullableString($validated['reference_code'] ?? null),
            'status' => (string) $validated['status'],
            'payment_status' => (string) $validated['payment_status'],
            'fulfillment_status' => (string) $validated['fulfillment_status'],
            'customer_name' => trim((string) $validated['customer_name']),
            'customer_email' => $this->nullableString($validated['customer_email'] ?? null),
            'customer_phone' => $this->nullableString($validated['customer_phone'] ?? null),
            'customer_company' => $this->nullableString($validated['customer_company'] ?? null),
            'customer_tax_number' => $this->nullableString($validated['customer_tax_number'] ?? null),
            'customer_tax_office' => $this->nullableString($validated['customer_tax_office'] ?? null),
            'currency' => (string) $validated['currency'],
            'order_discount_total' => $this->decimal($validated['order_discount_total'] ?? 0),
            'shipping_total' => $this->decimal($validated['shipping_total'] ?? 0),
            'payment_method' => $this->nullableString($validated['payment_method'] ?? null),
            'shipping_carrier' => $this->nullableString($validated['shipping_carrier'] ?? null),
            'tracking_number' => $this->nullableString($validated['tracking_number'] ?? null),
            'tracking_url' => $this->nullableString($validated['tracking_url'] ?? null),
            'billing_address' => $this->normalizeAddress($validated['billing_address'] ?? []),
            'shipping_address' => $this->normalizeAddress($validated['shipping_address'] ?? []),
            'customer_note' => $this->nullableString($validated['customer_note'] ?? null),
            'internal_note' => $this->nullableString($validated['internal_note'] ?? null),
            'custom_fields' => $validated['custom_fields'],
            'ordered_at' => $this->parseDateTime($validated['ordered_at'] ?? null, 'ordered_at'),
            'paid_at' => $this->parseDateTime($validated['paid_at'] ?? null, 'paid_at'),
            'shipped_at' => $this->parseDateTime($validated['shipped_at'] ?? null, 'shipped_at'),
            'delivered_at' => $this->parseDateTime($validated['delivered_at'] ?? null, 'delivered_at'),
            'cancelled_at' => $this->parseDateTime($validated['cancelled_at'] ?? null, 'cancelled_at'),
        ];
    }

    private function syncItems(EcommerceOrder $order, array $validated): void
    {
        $products = Product::query()
            ->whereIn('id', collect($validated['items'])->pluck('product_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        $subtotal = 0.0;
        $lineDiscount = 0.0;
        $taxTotal = 0.0;
        $rows = [];

        foreach ($validated['items'] as $item) {
            $product = filled($item['product_id'] ?? null) ? $products->get((int) $item['product_id']) : null;
            $quantity = max(0.001, $this->decimal($item['quantity'] ?? 1, 3));
            $unitPrice = $this->decimal(
                $item['unit_price'] ?? ($product?->sale_price ?? $product?->price ?? 0)
            );
            $lineSubtotal = round($quantity * $unitPrice, 2);
            $discount = min($lineSubtotal, $this->decimal($item['discount_total'] ?? 0));
            $taxRate = $this->decimal($item['tax_rate'] ?? ($product?->vat_rate ?? 0));
            $taxBase = max(0, $lineSubtotal - $discount);
            $lineTax = round($taxBase * ($taxRate / 100), 2);
            $lineTotal = round($taxBase + $lineTax, 2);

            $rows[] = [
                'product_id' => $product?->id,
                'product_title' => $this->nullableString($item['product_title'] ?? null) ?: (string) $product?->title,
                'sku' => $this->nullableString($item['sku'] ?? null) ?: $product?->sku,
                'barcode' => $this->nullableString($item['barcode'] ?? null) ?: $product?->barcode,
                'brand' => $this->nullableString($item['brand'] ?? null) ?: $product?->brand,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
                'discount_total' => $discount,
                'tax_rate' => $taxRate,
                'tax_total' => $lineTax,
                'total' => $lineTotal,
                'currency' => (string) $validated['currency'],
                'fulfillment_status' => $item['fulfillment_status'] ?? EcommerceOrder::FULFILLMENT_UNFULFILLED,
                'custom_fields' => $item['custom_fields'] ?? null,
            ];

            $subtotal += $lineSubtotal;
            $lineDiscount += $discount;
            $taxTotal += $lineTax;
        }

        foreach ($rows as $row) {
            if (!filled($row['product_title'])) {
                throw ValidationException::withMessages([
                    'items' => 'Ürün satırlarında başlık boş bırakılamaz.',
                ]);
            }
        }

        $orderDiscount = $this->decimal($validated['order_discount_total'] ?? 0);
        $shippingTotal = $this->decimal($validated['shipping_total'] ?? 0);
        $discountTotal = min($subtotal, $lineDiscount + $orderDiscount);
        $grandTotal = max(0, round($subtotal - $discountTotal + $taxTotal + $shippingTotal, 2));

        $order->items()->delete();
        $order->items()->createMany($rows);

        $order->forceFill([
            'subtotal' => round($subtotal, 2),
            'order_discount_total' => $orderDiscount,
            'discount_total' => round($discountTotal, 2),
            'shipping_total' => $shippingTotal,
            'tax_total' => round($taxTotal, 2),
            'grand_total' => $grandTotal,
        ])->save();
    }

    private function refreshPaymentTotals(EcommerceOrder $order): void
    {
        $order->load('transactions');

        $successful = $order->transactions->where('status', EcommerceOrderTransaction::STATUS_SUCCEEDED);
        $paid = (float) $successful
            ->whereIn('type', [
                EcommerceOrderTransaction::TYPE_SALE,
                EcommerceOrderTransaction::TYPE_CAPTURE,
                EcommerceOrderTransaction::TYPE_ADJUSTMENT,
            ])
            ->sum(fn (EcommerceOrderTransaction $transaction) => (float) $transaction->amount);
        $refunded = (float) $successful
            ->where('type', EcommerceOrderTransaction::TYPE_REFUND)
            ->sum(fn (EcommerceOrderTransaction $transaction) => (float) $transaction->amount);

        $paymentStatus = $order->payment_status;
        if ($refunded > 0 && $paid > 0 && $refunded >= $paid) {
            $paymentStatus = EcommerceOrder::PAYMENT_REFUNDED;
        } elseif ($refunded > 0) {
            $paymentStatus = EcommerceOrder::PAYMENT_PARTIALLY_REFUNDED;
        } elseif ($paid >= (float) $order->grand_total && (float) $order->grand_total > 0) {
            $paymentStatus = EcommerceOrder::PAYMENT_PAID;
        } elseif ($paid > 0) {
            $paymentStatus = EcommerceOrder::PAYMENT_PARTIAL;
        }

        $order->forceFill([
            'paid_total' => round($paid, 2),
            'refunded_total' => round($refunded, 2),
            'payment_status' => $paymentStatus,
            'paid_at' => $paid > 0 ? ($order->paid_at ?? now()) : $order->paid_at,
        ])->save();
    }

    private function applyShipmentState(EcommerceOrder $order, EcommerceShipment $shipment): void
    {
        $payload = [
            'shipping_carrier' => $shipment->carrier ?: $order->shipping_carrier,
            'tracking_number' => $shipment->tracking_number ?: $order->tracking_number,
            'tracking_url' => $shipment->tracking_url ?: $order->tracking_url,
        ];

        if ($shipment->status === EcommerceShipment::STATUS_SHIPPED) {
            $payload['status'] = EcommerceOrder::STATUS_SHIPPED;
            $payload['fulfillment_status'] = EcommerceOrder::FULFILLMENT_FULFILLED;
            $payload['shipped_at'] = $shipment->shipped_at ?? $order->shipped_at ?? now();
        }

        if ($shipment->status === EcommerceShipment::STATUS_DELIVERED) {
            $payload['status'] = EcommerceOrder::STATUS_COMPLETED;
            $payload['fulfillment_status'] = EcommerceOrder::FULFILLMENT_FULFILLED;
            $payload['shipped_at'] = $shipment->shipped_at ?? $order->shipped_at ?? now();
            $payload['delivered_at'] = $shipment->delivered_at ?? $order->delivered_at ?? now();
        }

        if ($shipment->status === EcommerceShipment::STATUS_RETURNED) {
            $payload['fulfillment_status'] = EcommerceOrder::FULFILLMENT_RETURNED;
        }

        if ($shipment->status === EcommerceShipment::STATUS_CANCELLED) {
            $payload['fulfillment_status'] = EcommerceOrder::FULFILLMENT_CANCELLED;
        }

        $order->forceFill($payload)->save();
    }

    private function recordHistory(
        EcommerceOrder $order,
        ?string $fromStatus,
        ?string $fromPaymentStatus,
        ?string $fromFulfillmentStatus,
        ?string $note = null
    ): void {
        EcommerceOrderStatusHistory::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'from_status' => $fromStatus,
            'to_status' => $order->status,
            'from_payment_status' => $fromPaymentStatus,
            'to_payment_status' => $order->payment_status,
            'from_fulfillment_status' => $fromFulfillmentStatus,
            'to_fulfillment_status' => $order->fulfillment_status,
            'note' => $this->nullableString($note),
        ]);
    }

    private function formPayload(): array
    {
        $products = Product::query()
            ->orderBy('title')
            ->limit(500)
            ->get(['id', 'title', 'sku', 'barcode', 'brand', 'price', 'sale_price', 'currency', 'vat_rate', 'stock']);

        return [
            'statusOptions' => EcommerceOrder::statusOptions(),
            'paymentStatusOptions' => EcommerceOrder::paymentStatusOptions(),
            'fulfillmentStatusOptions' => EcommerceOrder::fulfillmentStatusOptions(),
            'channelOptions' => $this->channelOptions(),
            'paymentIntegrations' => PaymentIntegration::query()
                ->orderByDesc('is_default')
                ->orderByDesc('is_active')
                ->orderBy('title')
                ->get(['id', 'title', 'provider', 'environment', 'is_active', 'is_default']),
            'members' => Member::query()
                ->orderBy('name')
                ->limit(300)
                ->get(['id', 'name', 'surname', 'email', 'phone']),
            'products' => $products,
            'productOptionsJson' => $products
                ->map(fn (Product $product) => [
                    'id' => (int) $product->id,
                    'title' => (string) $product->title,
                    'sku' => (string) ($product->sku ?? ''),
                    'barcode' => (string) ($product->barcode ?? ''),
                    'brand' => (string) ($product->brand ?? ''),
                    'unit_price' => (float) ($product->sale_price ?? $product->price ?? 0),
                    'currency' => (string) ($product->currency ?: 'TRY'),
                    'tax_rate' => (float) ($product->vat_rate ?? 0),
                    'stock' => is_null($product->stock) ? null : (int) $product->stock,
                ])
                ->values()
                ->all(),
        ];
    }

    private function stats(): array
    {
        return [
            'all' => EcommerceOrder::query()->count(),
            'pending' => EcommerceOrder::query()->whereIn('status', [EcommerceOrder::STATUS_PENDING, EcommerceOrder::STATUS_CONFIRMED])->count(),
            'paid' => EcommerceOrder::query()->where('payment_status', EcommerceOrder::PAYMENT_PAID)->count(),
            'shipping' => EcommerceOrder::query()->whereIn('fulfillment_status', [EcommerceOrder::FULFILLMENT_UNFULFILLED, EcommerceOrder::FULFILLMENT_PREPARING])->count(),
            'revenue' => EcommerceOrder::query()->whereNotIn('status', [EcommerceOrder::STATUS_CANCELLED])->sum('grand_total'),
        ];
    }

    private function channelOptions(): array
    {
        return [
            'admin' => 'Panel',
            'website' => 'Web Site',
            'marketplace' => 'Pazaryeri',
            'phone' => 'Telefon',
            'whatsapp' => 'WhatsApp',
            'instagram' => 'Instagram',
        ];
    }

    private function normalizeAddress(array $address): ?array
    {
        $normalized = collect([
            'name' => $address['name'] ?? null,
            'phone' => $address['phone'] ?? null,
            'line1' => $address['line1'] ?? null,
            'line2' => $address['line2'] ?? null,
            'district' => $address['district'] ?? null,
            'city' => $address['city'] ?? null,
            'postal_code' => $address['postal_code'] ?? null,
            'country' => $address['country'] ?? 'TR',
        ])
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => filled($value))
            ->all();

        return count($normalized) > 0 ? $normalized : null;
    }

    private function parseDateTime(?string $value, string $field = 'ordered_at'): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d', 'd.m.Y H:i', 'd.m.Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date;
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'Tarih formatı okunamadı.',
            ]);
        }
    }

    private function decodeJsonField(?string $value, string $field): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                $field => 'Geçerli bir JSON nesnesi girin.',
            ]);
        }

        return $decoded;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return filled($value) ? (string) $value : null;
    }

    private function decimal(mixed $value, int $precision = 2): float
    {
        $normalized = str_replace(',', '.', (string) ($value ?? 0));

        return round((float) $normalized, $precision);
    }
}
