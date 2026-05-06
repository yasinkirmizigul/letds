@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">E-Ticaret Operasyonu</span>
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Kupon ve Kampanya Yönetimi</h1>
                    <div class="text-sm text-muted-foreground">Sepet indirimi, yüzde indirim ve ücretsiz kargo kuponlarını yönetin.</div>
                </div>
            </div>
            @perm('ecommerce_coupons.create')
                <a href="{{ route('admin.ecommerce.coupons.create') }}" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-plus"></i>
                    Yeni Kupon
                </a>
            @endperm
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Toplam</div><div class="mt-2 text-3xl font-semibold">{{ $stats['all'] }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Aktif</div><div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Süresi Biten</div><div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['expired'] }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Kullanım</div><div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['used'] }}</div></div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5 flex-wrap gap-4">
                <div>
                    <h3 class="kt-card-title">Kuponlar</h3>
                    <div class="text-sm text-muted-foreground">Kod, durum ve kullanım sınırlarını takip edin.</div>
                </div>
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input name="q" value="{{ $search }}" class="kt-input w-full md:w-[240px]" placeholder="Kupon kodu veya ad ara">
                    <select name="status" class="kt-select w-full md:w-[160px]">
                        <option value="all" @selected($status === 'all')>Tümü</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="passive" @selected($status === 'passive')>Pasif</option>
                    </select>
                    <button type="submit" class="kt-btn kt-btn-light">Filtrele</button>
                </form>
            </div>

            <div class="kt-card-content grid gap-4 p-6">
                @forelse($coupons as $coupon)
                    <div class="rounded-[28px] app-surface-card p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="grid gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-lg font-semibold text-foreground">{{ $coupon->code }}</div>
                                    <span class="{{ $coupon->statusBadgeClass() }}">{{ $coupon->statusLabel() }}</span>
                                    <span class="kt-badge kt-badge-sm kt-badge-light">{{ $coupon->typeLabel() }}</span>
                                </div>
                                <div class="text-sm text-muted-foreground">{{ $coupon->name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    Değer: {{ number_format((float) $coupon->value, 2, ',', '.') }}
                                    · Kullanım: {{ $coupon->usage_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}
                                    · Tarih: {{ $coupon->starts_at?->format('d.m.Y') ?: 'Hemen' }} - {{ $coupon->ends_at?->format('d.m.Y') ?: 'Süresiz' }}
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @perm('ecommerce_coupons.update')
                                    <form method="POST" action="{{ route('admin.ecommerce.coupons.toggle', $coupon) }}" data-native-submit="true">
                                        @csrf
                                        @method('PATCH')
                                        <button class="kt-btn kt-btn-sm kt-btn-light" type="submit">{{ $coupon->is_active ? 'Pasifleştir' : 'Aktifleştir' }}</button>
                                    </form>
                                    <a href="{{ route('admin.ecommerce.coupons.edit', $coupon) }}" class="kt-btn kt-btn-sm kt-btn-primary">Düzenle</a>
                                @endperm
                                @perm('ecommerce_coupons.delete')
                                    <form method="POST" action="{{ route('admin.ecommerce.coupons.destroy', $coupon) }}" data-native-submit="true" onsubmit="return confirm('Bu kupon silinsin mi?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="kt-btn kt-btn-sm kt-btn-danger" type="submit">Sil</button>
                                    </form>
                                @endperm
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-border px-6 py-12 text-center">
                        <div class="text-lg font-semibold text-foreground">Henüz kupon yok.</div>
                        <div class="mt-2 text-sm text-muted-foreground">Satış kullanan siteler için kampanya kurgularını buradan başlatabilirsiniz.</div>
                    </div>
                @endforelse
            </div>

            @if($coupons->hasPages())
                <div class="kt-card-footer">{{ $coupons->links() }}</div>
            @endif
        </div>
    </div>
@endsection
