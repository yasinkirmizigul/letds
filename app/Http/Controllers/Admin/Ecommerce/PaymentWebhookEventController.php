<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Admin\Ecommerce\PaymentWebhookEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentWebhookEventController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->string('status', 'all');
        $provider = trim((string) $request->string('provider'));

        $events = PaymentWebhookEvent::query()
            ->with(['paymentIntegration:id,title,provider', 'order:id,order_number,customer_name'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($provider !== '', fn ($query) => $query->where('provider', 'like', "%{$provider}%"))
            ->latest('received_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.pages.ecommerce.webhooks.index', [
            'pageTitle' => 'Ödeme Webhook Kayıtları',
            'events' => $events,
            'status' => $status,
            'provider' => $provider,
            'statusOptions' => PaymentWebhookEvent::statusOptions(),
            'stats' => [
                'all' => PaymentWebhookEvent::query()->count(),
                'received' => PaymentWebhookEvent::query()->where('status', PaymentWebhookEvent::STATUS_RECEIVED)->count(),
                'failed' => PaymentWebhookEvent::query()->where('status', PaymentWebhookEvent::STATUS_FAILED)->count(),
                'processed' => PaymentWebhookEvent::query()->where('status', PaymentWebhookEvent::STATUS_PROCESSED)->count(),
            ],
        ]);
    }

    public function updateStatus(Request $request, PaymentWebhookEvent $event): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(PaymentWebhookEvent::statusOptions()))],
            'error_message' => ['nullable', 'string', 'max:3000'],
        ]);

        $event->forceFill([
            'status' => $validated['status'],
            'processed_at' => $validated['status'] === PaymentWebhookEvent::STATUS_PROCESSED
                ? ($event->processed_at ?? now())
                : $event->processed_at,
            'error_message' => $validated['error_message'] ?? null,
        ])->save();

        return back()->with('success', 'Webhook durumu güncellendi.');
    }
}
