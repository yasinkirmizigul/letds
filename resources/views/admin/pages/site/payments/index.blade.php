@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="site.payments.index">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">Ödeme Entegrasyonları</h1>
                    <div class="text-sm text-muted-foreground">
                        Sanal POS, global ödeme ağ geçidi ve havale altyapılarını tek panelden güvenli şekilde yönet.
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.site.payments.create') }}" class="kt-btn kt-btn-primary">
                    Yeni Entegrasyon Ekle
                </a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam Entegrasyon</div>
                <div class="mt-2 text-3xl font-semibold">{{ $stats['all'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Aktif</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Canlı Mod</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['live'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Varsayılan</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['default'] ?? 0 }}</div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($providerDefinitions as $providerKey => $definition)
                <a href="{{ route('admin.site.payments.create', ['provider' => $providerKey]) }}" class="rounded-[28px] app-surface-card p-5 transition hover:-translate-y-0.5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-foreground">{{ $definition['label'] ?? $providerKey }}</div>
                            <div class="mt-2 text-sm leading-7 text-muted-foreground">{{ $definition['description'] ?? '' }}</div>
                        </div>
                        <span class="kt-badge kt-badge-sm kt-badge-light">{{ $definition['integration_type'] ?? 'payment_gateway' }}</span>
                    </div>
                    <div class="mt-4 text-xs uppercase tracking-[0.22em] text-primary">Hazır Şablon ile Başlat</div>
                </a>
            @endforeach
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Entegrasyon Havuzu</h3>
                    <div class="text-sm text-muted-foreground">
                        Sağlayıcılarını filtrele, varsayılanı değiştir ve güvenlik durumlarını kontrol et.
                    </div>
                </div>

                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        class="kt-input w-full md:w-[240px]"
                        placeholder="Sağlayıcı veya başlık ara"
                    >

                    <select name="status" class="kt-select w-full md:w-[180px]" data-kt-select="true">
                        <option value="all" @selected($status === 'all')>Tüm durumlar</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="passive" @selected($status === 'passive')>Pasif</option>
                    </select>

                    <select name="environment" class="kt-select w-full md:w-[200px]" data-kt-select="true">
                        <option value="all" @selected($environment === 'all')>Tüm ortamlar</option>
                        @foreach($environmentOptions as $environmentKey => $label)
                            <option value="{{ $environmentKey }}" @selected($environment === $environmentKey)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="kt-btn kt-btn-light">Filtrele</button>
                </form>
            </div>

            <div class="kt-card-content grid gap-4 p-6">
                @forelse($integrations as $integration)
                    @php($health = $integration->healthBadge())

                    <div class="rounded-[28px] app-surface-card p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="grid gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-lg font-semibold text-foreground">{{ $integration->title }}</div>
                                    <span class="kt-badge kt-badge-sm kt-badge-light">{{ $integration->providerLabel() }}</span>
                                    <span class="{{ $integration->environmentBadgeClass() }}">{{ $integration->environmentLabel() }}</span>
                                    <span class="{{ $health['class'] }}">{{ $health['label'] }}</span>
                                    @if($integration->is_default)
                                        <span class="kt-badge kt-badge-sm kt-badge-light-primary">Varsayılan</span>
                                    @endif
                                </div>

                                <div class="text-sm text-muted-foreground">
                                    {{ $integration->providerDescription() ?: 'Bu kayıt ödeme sağlayıcısı yapılandırmasını tutar.' }}
                                </div>

                                <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                    <span>{{ $integration->integrationTypeLabel() }}</span>
                                    <span>•</span>
                                    <span>{{ $integration->is_active ? 'Aktif' : 'Pasif' }}</span>
                                    <span>•</span>
                                    <span>{{ count($integration->supported_currencies ?? []) > 0 ? implode(', ', $integration->supported_currencies) : 'Para birimi tanımsız' }}</span>
                                </div>

                                <div class="grid gap-2 text-xs text-muted-foreground">
                                    <div>Gizli alan rotasyonu: {{ optional($integration->credentials_rotated_at)->format('d.m.Y H:i') ?: 'Henüz kaydedilmedi' }}</div>
                                    <div>Ödeme yöntemleri: {{ count($integration->allowedPaymentMethodLabels()) > 0 ? implode(', ', $integration->allowedPaymentMethodLabels()) : 'Tanımsız' }}</div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if(!$integration->is_default)
                                    <form method="POST" action="{{ route('admin.site.payments.makeDefault', $integration) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="kt-btn kt-btn-light">Varsayılan Yap</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.site.payments.toggleActive', $integration) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="kt-btn kt-btn-light">
                                        {{ $integration->is_active ? 'Pasifleştir' : 'Aktifleştir' }}
                                    </button>
                                </form>

                                <a href="{{ route('admin.site.payments.edit', $integration) }}" class="kt-btn kt-btn-primary">Düzenle</a>

                                <form method="POST" action="{{ route('admin.site.payments.destroy', $integration) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="kt-btn kt-btn-danger" onclick="return confirm('Bu ödeme entegrasyonu silinsin mi?')">
                                        Sil
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-border px-6 py-12 text-center">
                        <div class="text-lg font-semibold">Henüz ödeme entegrasyonu yok.</div>
                        <div class="mt-2 text-sm text-muted-foreground">
                            İyzico, PayTR, Stripe veya başka bir sağlayıcı için ilk bağlantınızı buradan başlatabilirsiniz.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
