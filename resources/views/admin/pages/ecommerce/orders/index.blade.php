@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="ecommerce.orders.index">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">E-Ticaret Operasyonu</span>
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Sipariş Yönetimi</h1>
                    <div class="text-sm text-muted-foreground">
                        Ürün, ödeme, teslimat ve müşteri bilgisini tek operasyon ekranında takip edin.
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.products.index') }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-handcart"></i>
                    Ürünler
                </a>
                <a href="{{ route('admin.site.payments.index') }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-two-credit-cart"></i>
                    Ödemeler
                </a>
                @perm('ecommerce_orders.create')
                    <a href="{{ route('admin.ecommerce.orders.create') }}" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-plus"></i>
                        Yeni Sipariş
                    </a>
                @endperm
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam</div>
                <div class="mt-2 text-3xl font-semibold text-foreground">{{ $stats['all'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Aksiyon Bekleyen</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['pending'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Ödemesi Tamam</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['paid'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Kargo Bekleyen</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['shipping'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Ciro</div>
                <div class="mt-2 text-3xl font-semibold text-foreground">
                    {{ number_format((float) ($stats['revenue'] ?? 0), 2, ',', '.') }} TRY
                </div>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5 flex-wrap gap-4">
                <div>
                    <h3 class="kt-card-title">Sipariş Havuzu</h3>
                    <div class="text-sm text-muted-foreground">Durum, ödeme, teslimat ve kanal kırılımlarını filtreleyin.</div>
                </div>

                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        class="kt-input w-full md:w-[260px]"
                        placeholder="Sipariş, müşteri, ürün kodu, takip no ara"
                    >

                    <select name="status" class="kt-select w-full md:w-[190px]" data-kt-select="true">
                        <option value="all" @selected($status === 'all')>Tüm sipariş durumları</option>
                        @foreach($statusOptions as $key => $option)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>

                    <select name="payment_status" class="kt-select w-full md:w-[190px]" data-kt-select="true">
                        <option value="all" @selected($paymentStatus === 'all')>Tüm ödeme durumları</option>
                        @foreach($paymentStatusOptions as $key => $option)
                            <option value="{{ $key }}" @selected($paymentStatus === $key)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>

                    <select name="fulfillment_status" class="kt-select w-full md:w-[190px]" data-kt-select="true">
                        <option value="all" @selected($fulfillmentStatus === 'all')>Tüm teslimat durumları</option>
                        @foreach($fulfillmentStatusOptions as $key => $option)
                            <option value="{{ $key }}" @selected($fulfillmentStatus === $key)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>

                    <select name="channel" class="kt-select w-full md:w-[160px]" data-kt-select="true">
                        <option value="all" @selected($channel === 'all')>Tüm kanallar</option>
                        @foreach($channelOptions as $key => $label)
                            <option value="{{ $key }}" @selected($channel === $key)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="kt-btn kt-btn-light">Filtrele</button>
                </form>
            </div>

            <div class="kt-card-content p-0">
                <div class="kt-scrollable-x-auto overflow-y-hidden">
                    <table class="kt-table kt-table-border table-auto w-full">
                        <thead>
                        <tr>
                            <th class="min-w-[240px]">Sipariş</th>
                            <th class="min-w-[240px]">Müşteri</th>
                            <th class="min-w-[210px]">Durum</th>
                            <th class="min-w-[180px]">Ödeme</th>
                            <th class="min-w-[180px]">Teslimat</th>
                            <th class="min-w-[170px]">Tutar</th>
                            <th class="min-w-[160px]">Tarih</th>
                            <th class="w-[120px]"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    <div class="grid gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="font-semibold text-foreground hover:text-primary">
                                                {{ $order->order_number }}
                                            </a>
                                            <span class="kt-badge kt-badge-sm kt-badge-light">{{ $channelOptions[$order->channel] ?? $order->channel }}</span>
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ $order->reference_code ? 'Ref: ' . $order->reference_code : 'Referans eklenmemiş' }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ $order->items->count() }} kalem ürün
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="grid gap-1">
                                        <div class="font-medium text-foreground">{{ $order->customer_name }}</div>
                                        <div class="text-sm text-muted-foreground">{{ $order->customer_email ?: '-' }}</div>
                                        <div class="text-sm text-muted-foreground">{{ $order->customer_phone ?: '-' }}</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                                </td>
                                <td>
                                    <div class="grid gap-2">
                                        <span class="{{ $order->paymentStatusBadgeClass() }}">{{ $order->paymentStatusLabel() }}</span>
                                        <div class="text-xs text-muted-foreground">{{ $order->paymentIntegration?->title ?: ($order->payment_method ?: 'Ödeme yöntemi yok') }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="grid gap-2">
                                        <span class="{{ $order->fulfillmentStatusBadgeClass() }}">{{ $order->fulfillmentStatusLabel() }}</span>
                                        <div class="text-xs text-muted-foreground">{{ $order->tracking_number ?: 'Takip no yok' }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="grid gap-1">
                                        <div class="font-semibold text-foreground">{{ $order->money() }}</div>
                                        <div class="text-xs text-muted-foreground">Ödenen: {{ $order->money((float) $order->paid_total) }}</div>
                                    </div>
                                </td>
                                <td class="text-sm text-muted-foreground">
                                    <div class="grid gap-0.5">
                                        <span>{{ $order->ordered_at?->format('d.m.Y') ?: $order->created_at?->format('d.m.Y') }}</span>
                                        <span class="text-xs">{{ $order->ordered_at?->format('H:i') ?: $order->created_at?->format('H:i') }}</span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="kt-btn kt-btn-sm kt-btn-light">Detay</a>
                                        @perm('ecommerce_orders.update')
                                            <a href="{{ route('admin.ecommerce.orders.edit', $order) }}" class="kt-btn kt-btn-sm kt-btn-primary">Düzenle</a>
                                        @endperm
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12">
                                    <div class="grid place-items-center gap-2 text-center text-muted-foreground">
                                        <i class="ki-outline ki-basket text-4xl"></i>
                                        <div class="font-semibold text-foreground">Henüz sipariş yok</div>
                                        <div class="text-sm">İlk siparişi panelden oluşturabilir veya satış kanalı entegrasyonu sonrası buradan takip edebilirsiniz.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($orders->hasPages())
                <div class="kt-card-footer">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
