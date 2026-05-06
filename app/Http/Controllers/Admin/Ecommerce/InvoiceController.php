<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Admin\Ecommerce\EcommerceInvoice;
use App\Models\Admin\Ecommerce\EcommerceOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status', 'all');

        $invoices = EcommerceInvoice::query()
            ->with('order:id,order_number,customer_name,customer_email')
            ->when($search !== '', function ($query) use ($search) {
                $query
                    ->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($orderQuery) => $orderQuery
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%"));
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.ecommerce.invoices.index', [
            'pageTitle' => 'Fatura ve Belge Yönetimi',
            'invoices' => $invoices,
            'orders' => EcommerceOrder::query()
                ->latest()
                ->limit(100)
                ->get(['id', 'order_number', 'customer_name', 'grand_total', 'currency']),
            'search' => $search,
            'status' => $status,
            'typeOptions' => EcommerceInvoice::typeOptions(),
            'statusOptions' => EcommerceInvoice::statusOptions(),
            'stats' => [
                'all' => EcommerceInvoice::query()->count(),
                'issued' => EcommerceInvoice::query()->where('status', EcommerceInvoice::STATUS_ISSUED)->count(),
                'draft' => EcommerceInvoice::query()->where('status', EcommerceInvoice::STATUS_DRAFT)->count(),
                'total' => EcommerceInvoice::query()->where('status', '!=', EcommerceInvoice::STATUS_CANCELLED)->sum('grand_total'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:ecommerce_orders,id'],
            'type' => ['required', Rule::in(array_keys(EcommerceInvoice::typeOptions()))],
            'status' => ['required', Rule::in(array_keys(EcommerceInvoice::statusOptions()))],
            'issued_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $order = EcommerceOrder::query()->with('items')->findOrFail($validated['order_id']);

        EcommerceInvoice::query()->create([
            'order_id' => $order->id,
            'type' => $validated['type'],
            'status' => $validated['status'],
            'currency' => $order->currency ?: 'TRY',
            'subtotal' => $order->subtotal,
            'tax_total' => $order->tax_total,
            'grand_total' => $order->grand_total,
            'billing_snapshot' => [
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'customer_company' => $order->customer_company,
                'customer_tax_number' => $order->customer_tax_number,
                'customer_tax_office' => $order->customer_tax_office,
                'billing_address' => $order->billing_address,
            ],
            'line_snapshot' => $order->items
                ->map(fn ($item) => [
                    'title' => $item->product_title,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                    'total' => $item->total,
                ])
                ->values()
                ->all(),
            'issued_at' => $validated['issued_at'] ?? ($validated['status'] === EcommerceInvoice::STATUS_ISSUED ? now() : null),
            'due_at' => $validated['due_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Belge oluşturuldu.');
    }

    public function updateStatus(Request $request, EcommerceInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(EcommerceInvoice::statusOptions()))],
        ]);

        $invoice->forceFill([
            'status' => $validated['status'],
            'issued_at' => $validated['status'] === EcommerceInvoice::STATUS_ISSUED
                ? ($invoice->issued_at ?? now())
                : $invoice->issued_at,
        ])->save();

        return back()->with('success', 'Belge durumu güncellendi.');
    }
}
