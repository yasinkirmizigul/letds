@extends('admin.layouts.main.app')

@section('content')
    @php
        $addressLine = function (?array $address): string {
            if (!$address) {
                return '-';
            }

            return collect([
                $address['name'] ?? null,
                $address['phone'] ?? null,
                $address['line1'] ?? null,
                $address['line2'] ?? null,
                trim(($address['district'] ?? '') . ' / ' . ($address['city'] ?? ''), ' /'),
                $address['postal_code'] ?? null,
                $address['country'] ?? null,
            ])->filter()->implode(', ');
        };
    @endphp

    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="ecommerce.orders.show">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                    <span class="{{ $order->paymentStatusBadgeClass() }}">{{ $order->paymentStatusLabel() }}</span>
                    <span class="{{ $order->fulfillmentStatusBadgeClass() }}">{{ $order->fulfillmentStatusLabel() }}</span>
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-foreground">{{ $order->order_number }}</h1>
                    <div class="text-sm text-muted-foreground">
                        {{ $order->customer_name }} için {{ $order->ordered_at?->format('d.m.Y H:i') ?: $order->created_at?->format('d.m.Y H:i') }} tarihinde açıldı.
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.ecommerce.orders.index') }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-left"></i>
                    Listeye Dön
                </a>
                @perm('ecommerce_orders.update')
                    <a href="{{ route('admin.ecommerce.orders.edit', $order) }}" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-pencil"></i>
                        Düzenle
                    </a>
                @endperm
                @perm('ecommerce_orders.delete')
                    <form method="POST" action="{{ route('admin.ecommerce.orders.destroy', $order) }}" data-ajax-redirect="true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="kt-btn kt-btn-danger" onclick="return confirm('Bu sipariş arşive alınsın mı?')">
                            <i class="ki-filled ki-trash"></i>
                            Arşivle
                        </button>
                    </form>
                @endperm
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam</div>
                <div class="mt-2 text-3xl font-semibold text-foreground">{{ $order->money() }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Ödenen</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $order->money((float) $order->paid_total) }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">İade</div>
                <div class="mt-2 text-3xl font-semibold text-danger">{{ $order->money((float) $order->refunded_total) }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Kalan</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $order->money($order->balanceDue()) }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Kalem</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $order->items->count() }}</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_420px]">
            <div class="grid gap-6">
                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Ürünler</h3>
                            <div class="text-sm text-muted-foreground">Siparişe bağlı katalog ürünleri ve manuel satırlar.</div>
                        </div>
                    </div>
                    <div class="kt-card-content p-0">
                        <div class="kt-scrollable-x-auto overflow-y-hidden">
                            <table class="kt-table kt-table-border table-auto w-full">
                                <thead>
                                <tr>
                                    <th class="min-w-[280px]">Ürün</th>
                                    <th class="min-w-[110px]">Adet</th>
                                    <th class="min-w-[140px]">Birim</th>
                                    <th class="min-w-[140px]">İndirim</th>
                                    <th class="min-w-[140px]">KDV</th>
                                    <th class="min-w-[150px]">Toplam</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="grid gap-2">
                                                <div class="font-semibold text-foreground">{{ $item->product_title }}</div>
                                                <div class="flex flex-wrap gap-1">
                                                    @if($item->product)
                                                        <a href="{{ route('admin.products.edit', $item->product) }}" class="kt-badge kt-badge-sm kt-badge-light-primary">Ürün #{{ $item->product_id }}</a>
                                                    @endif
                                                    @if($item->sku)
                                                        <span class="kt-badge kt-badge-sm kt-badge-light">Ürün kodu: {{ $item->sku }}</span>
                                                    @endif
                                                    @if($item->brand)
                                                        <span class="kt-badge kt-badge-sm kt-badge-light">{{ $item->brand }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ number_format((float) $item->quantity, 3, ',', '.') }}</td>
                                        <td>{{ number_format((float) $item->unit_price, 2, ',', '.') }} {{ $item->currency }}</td>
                                        <td>{{ number_format((float) $item->discount_total, 2, ',', '.') }} {{ $item->currency }}</td>
                                        <td>{{ number_format((float) $item->tax_total, 2, ',', '.') }} {{ $item->currency }} <span class="text-xs text-muted-foreground">(%{{ number_format((float) $item->tax_rate, 2, ',', '.') }})</span></td>
                                        <td class="font-semibold text-foreground">{{ number_format((float) $item->total, 2, ',', '.') }} {{ $item->currency }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">Ödeme Hareketleri</h3>
                                <div class="text-sm text-muted-foreground">Ödeme sağlayıcı, havale veya manuel tahsilat kayıtları.</div>
                            </div>
                        </div>
                        <div class="kt-card-content p-6 grid gap-4">
                            @forelse($order->transactions as $transaction)
                                <div class="rounded-2xl border border-border p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="grid gap-1">
                                            <div class="font-semibold text-foreground">{{ $transactionTypeOptions[$transaction->type] ?? $transaction->type }}</div>
                                            <div class="text-sm text-muted-foreground">{{ $transaction->paymentIntegration?->title ?: 'Manuel işlem' }}</div>
                                        </div>
                                        <span class="{{ $transaction->statusBadgeClass() }}">{{ $transactionStatusOptions[$transaction->status] ?? $transaction->status }}</span>
                                    </div>
                                    <div class="mt-3 grid gap-1 text-sm text-muted-foreground">
                                        <div>Tutar: <b class="text-foreground">{{ number_format((float) $transaction->amount, 2, ',', '.') }} {{ $transaction->currency }}</b></div>
                                        <div>Referans: {{ $transaction->gateway_reference ?: '-' }}</div>
                                        <div>İşlem tarihi: {{ $transaction->processed_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-border p-5 text-sm text-muted-foreground">Henüz ödeme hareketi yok.</div>
                            @endforelse

                            @perm('ecommerce_orders.payments')
                                <form method="POST" action="{{ route('admin.ecommerce.orders.transactions.store', $order) }}" class="grid gap-4 border-t border-border pt-5" data-ajax-redirect="true">
                                    @csrf
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <select name="type" class="kt-select" data-kt-select="true">
                                            @foreach($transactionTypeOptions as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select name="status" class="kt-select" data-kt-select="true">
                                            @foreach($transactionStatusOptions as $key => $label)
                                                <option value="{{ $key }}" @selected($key === 'succeeded')>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <input name="amount" type="number" step="0.01" min="0" class="kt-input" value="{{ number_format($order->balanceDue(), 2, '.', '') }}" placeholder="Tutar">
                                        <input name="currency" maxlength="3" class="kt-input uppercase" value="{{ $order->currency }}">
                                    </div>
                                    <select name="payment_integration_id" class="kt-select" data-kt-select="true">
                                        <option value="">Sipariş entegrasyonu / manuel</option>
                                        @foreach($paymentIntegrations as $integration)
                                            <option value="{{ $integration->id }}" @selected($order->payment_integration_id === $integration->id)>{{ $integration->title }}</option>
                                        @endforeach
                                    </select>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <input name="gateway_transaction_id" class="kt-input" placeholder="Sağlayıcı işlem no">
                                        <input name="gateway_reference" class="kt-input" placeholder="Referans">
                                    </div>
                                    <input name="processed_at" class="kt-input" data-app-date-picker="true" data-app-date-mode="datetime" data-initial-value="{{ now()->format('Y-m-d H:i') }}" placeholder="İşlem tarihi">
                                    <textarea name="notes" rows="3" class="kt-textarea" placeholder="Ödeme notu"></textarea>
                                    <button type="submit" class="kt-btn kt-btn-primary justify-self-start">
                                        <i class="ki-filled ki-plus"></i>
                                        Ödeme Hareketi Ekle
                                    </button>
                                </form>
                            @endperm
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">Kargo Kayıtları</h3>
                                <div class="text-sm text-muted-foreground">Parçalı gönderim ve takip numarası geçmişi.</div>
                            </div>
                        </div>
                        <div class="kt-card-content p-6 grid gap-4">
                            @forelse($order->shipments as $shipment)
                                <div class="rounded-2xl border border-border p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="grid gap-1">
                                            <div class="font-semibold text-foreground">{{ $shipment->carrier ?: 'Kargo firması yok' }}</div>
                                            <div class="text-sm text-muted-foreground">{{ $shipment->tracking_number ?: 'Takip no yok' }}</div>
                                        </div>
                                        <span class="{{ $shipment->statusBadgeClass() }}">{{ $shipmentStatusOptions[$shipment->status] ?? $shipment->status }}</span>
                                    </div>
                                    <div class="mt-3 grid gap-1 text-sm text-muted-foreground">
                                        <div>Paket: {{ $shipment->package_count }}</div>
                                        <div>Kargoya çıkış: {{ $shipment->shipped_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                        <div>Teslim: {{ $shipment->delivered_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-border p-5 text-sm text-muted-foreground">Henüz kargo kaydı yok.</div>
                            @endforelse

                            @perm('ecommerce_orders.shipments')
                                <form method="POST" action="{{ route('admin.ecommerce.orders.shipments.store', $order) }}" class="grid gap-4 border-t border-border pt-5" data-ajax-redirect="true">
                                    @csrf
                                    <select name="status" class="kt-select" data-kt-select="true">
                                        @foreach($shipmentStatusOptions as $key => $label)
                                            <option value="{{ $key }}" @selected($key === 'shipped')>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <input name="carrier" class="kt-input" value="{{ $order->shipping_carrier }}" placeholder="Kargo firması">
                                        <input name="package_count" type="number" min="1" class="kt-input" value="1" placeholder="Paket">
                                    </div>
                                    <input name="tracking_number" class="kt-input" value="{{ $order->tracking_number }}" placeholder="Takip numarası">
                                    <input name="tracking_url" class="kt-input" value="{{ $order->tracking_url }}" placeholder="Takip bağlantısı">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <input name="shipped_at" class="kt-input" data-app-date-picker="true" data-app-date-mode="datetime" data-initial-value="{{ now()->format('Y-m-d H:i') }}" placeholder="Kargo tarihi">
                                        <input name="delivered_at" class="kt-input" data-app-date-picker="true" data-app-date-mode="datetime" placeholder="Teslim tarihi">
                                    </div>
                                    <textarea name="notes" rows="3" class="kt-textarea" placeholder="Kargo notu"></textarea>
                                    <button type="submit" class="kt-btn kt-btn-primary justify-self-start">
                                        <i class="ki-filled ki-plus"></i>
                                        Kargo Kaydı Ekle
                                    </button>
                                </form>
                            @endperm
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 self-start xl:sticky xl:top-6">
                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Müşteri ve Kanal</h3>
                            <div class="text-sm text-muted-foreground">Siparişin müşteri, kanal ve ödeme bağlantısı.</div>
                        </div>
                    </div>
                    <div class="kt-card-content p-6 grid gap-4 text-sm">
                        <div>
                            <div class="text-muted-foreground">Müşteri</div>
                            <div class="mt-1 font-semibold text-foreground">{{ $order->customer_name }}</div>
                            <div class="text-muted-foreground">{{ $order->customer_email ?: '-' }}</div>
                            <div class="text-muted-foreground">{{ $order->customer_phone ?: '-' }}</div>
                        </div>
                        <div>
                            <div class="text-muted-foreground">Üye</div>
                            <div class="mt-1 text-foreground">{{ $order->member?->full_name ?: 'Manuel müşteri' }}</div>
                        </div>
                        <div>
                            <div class="text-muted-foreground">Ödeme Entegrasyonu</div>
                            <div class="mt-1 text-foreground">{{ $order->paymentIntegration?->title ?: 'Entegrasyonsuz' }}</div>
                        </div>
                        <div>
                            <div class="text-muted-foreground">Kanal / Referans</div>
                            <div class="mt-1 text-foreground">{{ $channelOptions[$order->channel] ?? $order->channel }} / {{ $order->reference_code ?: '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Adresler</h3>
                            <div class="text-sm text-muted-foreground">Fatura ve teslimat adres özeti.</div>
                        </div>
                    </div>
                    <div class="kt-card-content p-6 grid gap-4 text-sm">
                        <div>
                            <div class="font-semibold text-foreground">Fatura</div>
                            <div class="mt-2 leading-7 text-muted-foreground">{{ $addressLine($order->billing_address) }}</div>
                        </div>
                        <div class="border-t border-border pt-4">
                            <div class="font-semibold text-foreground">Teslimat</div>
                            <div class="mt-2 leading-7 text-muted-foreground">{{ $addressLine($order->shipping_address) }}</div>
                        </div>
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Toplam Kırılımı</h3>
                            <div class="text-sm text-muted-foreground">Finansal özet.</div>
                        </div>
                    </div>
                    <div class="kt-card-content p-6 grid gap-3 text-sm">
                        @foreach([
                            'Ara toplam' => $order->subtotal,
                            'İndirim' => $order->discount_total,
                            'Kargo / hizmet' => $order->shipping_total,
                            'KDV' => $order->tax_total,
                        ] as $label => $amount)
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-muted-foreground">{{ $label }}</span>
                                <b class="text-foreground">{{ $order->money((float) $amount) }}</b>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between gap-3 border-t border-border pt-3 text-base">
                            <span class="font-semibold text-foreground">Genel toplam</span>
                            <b class="text-primary">{{ $order->money() }}</b>
                        </div>
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Durum Geçmişi</h3>
                            <div class="text-sm text-muted-foreground">Sipariş, ödeme ve teslimat değişimleri.</div>
                        </div>
                    </div>
                    <div class="kt-card-content p-6 grid gap-4">
                        @forelse($order->histories as $history)
                            <div class="relative grid gap-2 border-s border-border ps-4">
                                <span class="absolute -start-[5px] top-1 size-2.5 rounded-full bg-primary"></span>
                                <div class="text-sm font-semibold text-foreground">{{ $history->created_at?->format('d.m.Y H:i') }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ $history->from_status ?: '-' }} → {{ $history->to_status ?: '-' }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ $history->user?->name ?: 'Sistem' }}{{ $history->note ? ' · ' . $history->note : '' }}
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-muted-foreground">Henüz geçmiş kaydı yok.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
