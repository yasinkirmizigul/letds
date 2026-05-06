@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">E-Ticaret Operasyonu</span>
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Fatura ve Belge Yönetimi</h1>
                    <div class="text-sm text-muted-foreground">Siparişlerden fatura, proforma ve iade belgesi kayıtları oluşturun.</div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Toplam</div><div class="mt-2 text-3xl font-semibold">{{ $stats['all'] }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Kesilen</div><div class="mt-2 text-3xl font-semibold text-success">{{ $stats['issued'] }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Taslak</div><div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['draft'] }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Belge Tutarı</div><div class="mt-2 text-3xl font-semibold text-primary">{{ number_format((float) $stats['total'], 2, ',', '.') }} TRY</div></div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="kt-card">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div>
                        <h3 class="kt-card-title">Belgeler</h3>
                        <div class="text-sm text-muted-foreground">Sipariş bağlantılı mali belge kayıtlarını takip edin.</div>
                    </div>
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <input name="q" value="{{ $search }}" class="kt-input w-full md:w-[240px]" placeholder="Belge, sipariş veya müşteri ara">
                        <select name="status" class="kt-select w-full md:w-[160px]">
                            <option value="all" @selected($status === 'all')>Tüm durumlar</option>
                            @foreach($statusOptions as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" @selected($status === $statusKey)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="kt-btn kt-btn-light">Filtrele</button>
                    </form>
                </div>

                <div class="kt-card-content grid gap-4 p-6">
                    @forelse($invoices as $invoice)
                        <div class="rounded-[28px] app-surface-card p-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="grid gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="text-lg font-semibold text-foreground">{{ $invoice->invoice_number }}</div>
                                        <span class="kt-badge kt-badge-sm kt-badge-light">{{ $typeOptions[$invoice->type] ?? $invoice->type }}</span>
                                        <span class="kt-badge kt-badge-sm kt-badge-light-primary">{{ $statusOptions[$invoice->status] ?? $invoice->status }}</span>
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ $invoice->order?->order_number ?: 'Sipariş silinmiş' }} · {{ $invoice->order?->customer_name ?: '-' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ $invoice->money() }} · Kesim: {{ $invoice->issued_at?->format('d.m.Y H:i') ?: 'Henüz kesilmedi' }}
                                    </div>
                                </div>

                                @perm('ecommerce_invoices.update')
                                    <form method="POST" action="{{ route('admin.ecommerce.invoices.status', $invoice) }}" class="flex items-center gap-2" data-native-submit="true">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="kt-select w-[140px]">
                                            @foreach($statusOptions as $statusKey => $statusLabel)
                                                <option value="{{ $statusKey }}" @selected($invoice->status === $statusKey)>{{ $statusLabel }}</option>
                                            @endforeach
                                        </select>
                                        <button class="kt-btn kt-btn-sm kt-btn-light-primary" type="submit">Kaydet</button>
                                    </form>
                                @endperm
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-border px-6 py-12 text-center">
                            <div class="text-lg font-semibold text-foreground">Henüz belge yok.</div>
                            <div class="mt-2 text-sm text-muted-foreground">Siparişlerden belge kaydı oluşturarak mali takip başlatabilirsiniz.</div>
                        </div>
                    @endforelse
                </div>

                @if($invoices->hasPages())
                    <div class="kt-card-footer">{{ $invoices->links() }}</div>
                @endif
            </div>

            @perm('ecommerce_invoices.create')
                <form method="POST" action="{{ route('admin.ecommerce.invoices.store') }}" class="kt-card self-start xl:sticky xl:top-6" data-native-submit="true">
                    @csrf
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Yeni Belge</h3>
                            <div class="text-sm text-muted-foreground">Sipariş tutarlarından anlık belge kaydı oluşturun.</div>
                        </div>
                    </div>
                    <div class="kt-card-content grid gap-4 p-6">
                        <div class="grid gap-2">
                            <label class="kt-form-label">Sipariş</label>
                            <select name="order_id" class="kt-select">
                                <option value="">Sipariş seçin</option>
                                @foreach($orders as $order)
                                    <option value="{{ $order->id }}">{{ $order->order_number }} · {{ $order->customer_name }} · {{ number_format((float) $order->grand_total, 2, ',', '.') }} {{ $order->currency }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <label class="kt-form-label">Belge Tipi</label>
                            <select name="type" class="kt-select">
                                @foreach($typeOptions as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <label class="kt-form-label">Durum</label>
                            <select name="status" class="kt-select">
                                @foreach($statusOptions as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <label class="kt-form-label">Kesim Tarihi</label>
                            <div class="kt-input w-full">
                                <i class="ki-outline ki-calendar"></i>
                                <input
                                    name="issued_at"
                                    class="grow"
                                    type="text"
                                    readonly
                                    placeholder="GG.AA.YYYY SS:DD"
                                    value="{{ old('issued_at') }}"
                                    data-app-date-picker="true"
                                    data-app-date-mode="datetime"
                                    data-app-date-format="DD.MM.YYYY HH:mm"
                                >
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <label class="kt-form-label">Vade Tarihi</label>
                            <div class="kt-input w-full">
                                <i class="ki-outline ki-calendar"></i>
                                <input
                                    name="due_at"
                                    class="grow"
                                    type="text"
                                    readonly
                                    placeholder="GG.AA.YYYY SS:DD"
                                    value="{{ old('due_at') }}"
                                    data-app-date-picker="true"
                                    data-app-date-mode="datetime"
                                    data-app-date-format="DD.MM.YYYY HH:mm"
                                >
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <label class="kt-form-label">Not</label>
                            <textarea name="notes" rows="4" class="kt-textarea"></textarea>
                        </div>
                    </div>
                    <div class="kt-card-footer justify-end">
                        <button type="submit" class="kt-btn kt-btn-primary">Belge Oluştur</button>
                    </div>
                </form>
            @endperm
        </div>
    </div>
@endsection
