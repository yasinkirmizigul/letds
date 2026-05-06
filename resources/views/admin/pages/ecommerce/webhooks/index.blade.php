@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Ödeme Operasyonu</span>
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Ödeme Webhook Kayıtları</h1>
                    <div class="text-sm text-muted-foreground">Ödeme sağlayıcılarından gelen olayları, hata durumlarını ve işleme sonucunu izleyin.</div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Toplam</div><div class="mt-2 text-3xl font-semibold">{{ $stats['all'] }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Alındı</div><div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['received'] }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">İşlendi</div><div class="mt-2 text-3xl font-semibold text-success">{{ $stats['processed'] }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Hatalı</div><div class="mt-2 text-3xl font-semibold text-danger">{{ $stats['failed'] }}</div></div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5 flex-wrap gap-4">
                <div>
                    <h3 class="kt-card-title">Webhook Olayları</h3>
                    <div class="text-sm text-muted-foreground">Entegrasyon sonrası gelen olayların denetim defteri.</div>
                </div>
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input name="provider" value="{{ $provider }}" class="kt-input w-full md:w-[220px]" placeholder="Sağlayıcı ara">
                    <select name="status" class="kt-select w-full md:w-[170px]">
                        <option value="all" @selected($status === 'all')>Tüm durumlar</option>
                        @foreach($statusOptions as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected($status === $statusKey)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="kt-btn kt-btn-light">Filtrele</button>
                </form>
            </div>

            <div class="kt-card-content grid gap-4 p-6">
                @forelse($events as $event)
                    <div class="rounded-[28px] app-surface-card p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="grid gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-lg font-semibold text-foreground">{{ $event->provider }}</div>
                                    <span class="kt-badge kt-badge-sm kt-badge-light">{{ $event->event_type ?: 'event' }}</span>
                                    <span class="kt-badge kt-badge-sm {{ $event->status === 'failed' ? 'kt-badge-light-danger' : 'kt-badge-light-primary' }}">
                                        {{ $statusOptions[$event->status] ?? $event->status }}
                                    </span>
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ $event->paymentIntegration?->title ?: 'Entegrasyon eşleşmedi' }}
                                    · {{ $event->order?->order_number ?: 'Sipariş eşleşmedi' }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Event ID: {{ $event->event_id ?: '-' }} · Alım: {{ $event->received_at?->format('d.m.Y H:i') }}
                                </div>
                                @if($event->error_message)
                                    <div class="text-sm text-danger">{{ $event->error_message }}</div>
                                @endif
                            </div>

                            @perm('ecommerce_webhooks.update')
                                <form method="POST" action="{{ route('admin.ecommerce.webhooks.status', $event) }}" class="grid gap-2 min-w-[260px]" data-native-submit="true">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="kt-select">
                                        @foreach($statusOptions as $statusKey => $statusLabel)
                                            <option value="{{ $statusKey }}" @selected($event->status === $statusKey)>{{ $statusLabel }}</option>
                                        @endforeach
                                    </select>
                                    <input name="error_message" class="kt-input" value="{{ $event->error_message }}" placeholder="Hata notu">
                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-light-primary">Durumu Kaydet</button>
                                </form>
                            @endperm
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-border px-6 py-12 text-center">
                        <div class="text-lg font-semibold text-foreground">Henüz webhook kaydı yok.</div>
                        <div class="mt-2 text-sm text-muted-foreground">Ödeme sağlayıcı entegrasyonları bağlandığında olaylar burada izlenecek.</div>
                    </div>
                @endforelse
            </div>

            @if($events->hasPages())
                <div class="kt-card-footer">{{ $events->links() }}</div>
            @endif
        </div>
    </div>
@endsection
