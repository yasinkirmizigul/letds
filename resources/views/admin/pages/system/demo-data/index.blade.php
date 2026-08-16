@extends('admin.layouts.main.app')

@section('content')
    @php
        $modules = collect($overview['modules'] ?? []);
        $protected = $overview['protected'] ?? [];
        $resettableTotal = (int) ($overview['resettable_total'] ?? 0);
        $demoAccounts = $overview['demo_accounts'] ?? collect();
    @endphp

    <div class="w-full pb-8" data-page="demo-data.index">
        @include('admin.partials._flash')

        <div class="grid gap-6">
            <section class="relative overflow-hidden rounded-2xl border border-primary/25 bg-gradient-to-br from-primary/15 via-background to-info/10 p-5 sm:p-7">
                <div class="pointer-events-none absolute -end-16 -top-20 size-64 rounded-full bg-primary/10 blur-3xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-24 start-1/3 size-56 rounded-full bg-info/10 blur-3xl" aria-hidden="true"></div>

                <div class="relative grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(22rem,0.65fr)] xl:items-center">
                    <div class="max-w-3xl">
                        <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Yalnızca Super Admin</span>
                        <h1 class="mt-4 text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Örnek Veri Fabrikası</h1>
                        <p class="mt-3 max-w-[72ch] text-sm leading-6 text-muted-foreground sm:text-base">
                            Blogdan siparişe, randevudan değerlendirmeye kadar paneldeki ana modülleri gerçekçi ve birbirine bağlı verilerle doldur. Sunum, geliştirme ve uçtan uca akış testleri için hazır bir çalışma ortamı oluştur.
                        </p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <span class="kt-badge kt-badge-light"><i class="ki-filled ki-shield-tick"></i> Transaction korumalı</span>
                            <span class="kt-badge kt-badge-light"><i class="ki-filled ki-link"></i> İlişkisel veri</span>
                            <span class="kt-badge kt-badge-light"><i class="ki-filled ki-picture"></i> Yerel SVG medya</span>
                            <span class="kt-badge kt-badge-light"><i class="ki-filled ki-lock-2"></i> Eşzamanlı işlem kilidi</span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border/80 bg-background/80 p-5 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm text-muted-foreground">Temizlenebilir kayıt</div>
                                <div class="mt-1 text-3xl font-semibold text-foreground">{{ number_format($resettableTotal) }}</div>
                            </div>
                            <span class="inline-flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <i class="ki-filled ki-data text-2xl"></i>
                            </span>
                        </div>
                        <div class="mt-4 border-t border-border pt-4 text-xs leading-5 text-muted-foreground">
                            Üretim mevcut verileri silmez; her çalıştırmada yeni ve benzersiz bir demo veri seti ekler.
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Modül veri özeti">
                @foreach($modules as $module)
                    <article class="kt-card transition-colors hover:border-primary/35">
                        <div class="kt-card-content flex items-center gap-4 p-5">
                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <i class="{{ $module['icon'] }} text-xl"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm text-muted-foreground">{{ $module['label'] }}</div>
                                <div class="mt-1 text-2xl font-semibold text-foreground">{{ number_format((int) $module['count']) }}</div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="grid items-start gap-6 xl:grid-cols-2">
                <article class="kt-card overflow-hidden border-success/25">
                    <div class="kt-card-header min-h-0 gap-4 border-b border-success/20 bg-success/5 py-5">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-success/15 text-success">
                                <i class="ki-filled ki-plus-circle text-xl"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-semibold text-foreground">Örnek veri üret</h2>
                                <p class="mt-1 text-sm text-muted-foreground">Mevcut kayıtları korur ve yeni bir demo seti ekler.</p>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card-content grid gap-5 p-5 sm:p-6">
                        <ul class="grid gap-3 text-sm text-muted-foreground">
                            <li class="flex items-start gap-3"><i class="ki-filled ki-check-circle mt-0.5 text-success"></i><span>Uzman kullanıcı, üye ve iletişim senaryoları oluşturulur.</span></li>
                            <li class="flex items-start gap-3"><i class="ki-filled ki-check-circle mt-0.5 text-success"></i><span>Blog, proje, ürün, medya, galeri ve CMS içerikleri bağlanır.</span></li>
                            <li class="flex items-start gap-3"><i class="ki-filled ki-check-circle mt-0.5 text-success"></i><span>Randevu, sipariş, stok, fatura ve değerlendirme akışları hazırlanır.</span></li>
                        </ul>

                        <form
                            method="POST"
                            action="{{ route('admin.demo-data.generate') }}"
                            data-demo-action="generate"
                            data-block-ui-title="Örnek veriler üretiliyor"
                            data-block-ui-message="Modüller ve ilişkili kayıtlar hazırlanıyor. Lütfen bekleyin."
                        >
                            @csrf
                            <button type="submit" class="kt-btn kt-btn-success w-full justify-center sm:w-auto">
                                <i class="ki-filled ki-data"></i>
                                Örnek Veri Üret
                            </button>
                        </form>
                    </div>
                </article>

                <article class="kt-card overflow-hidden border-danger/30">
                    <div class="kt-card-header min-h-0 gap-4 border-b border-danger/20 bg-danger/5 py-5">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-danger/15 text-danger">
                                <i class="ki-filled ki-trash-square text-xl"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-semibold text-foreground">Operasyon verilerini sıfırla</h2>
                                <p class="mt-1 text-sm text-muted-foreground">İçerik ve işlem verilerini kalıcı olarak temizler.</p>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card-content grid gap-5 p-5 sm:p-6">
                        <div class="kt-alert kt-alert-danger">
                            <i class="ki-filled ki-information-2 text-lg"></i>
                            <div class="kt-alert-text">
                                Bu işlem yalnızca demo kayıtlarını değil, koruma listesi dışındaki tüm operasyon verilerini siler ve geri alınamaz.
                            </div>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('admin.demo-data.reset') }}"
                            data-demo-action="reset"
                            data-block-ui-title="Veriler sıfırlanıyor"
                            data-block-ui-message="İlişkiler ve dosyalar güvenli sırayla temizleniyor. Lütfen bekleyin."
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="kt-btn kt-btn-destructive w-full justify-center sm:w-auto" @disabled($resettableTotal === 0)>
                                <i class="ki-filled ki-trash"></i>
                                Verileri Sıfırla
                            </button>
                        </form>
                    </div>
                </article>
            </section>

            <section class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(22rem,0.9fr)]">
                <article class="kt-card">
                    <div class="kt-card-header min-h-0 py-5">
                        <div>
                            <h2 class="font-semibold text-foreground">Sıfırlamada korunan çekirdek</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Panelin çalışması ve mevcut tasarım yapılandırmasının bozulmaması için saklanır.</p>
                        </div>
                        <span class="kt-badge kt-badge-light-success">Güvenli alan</span>
                    </div>
                    <div class="kt-card-content grid gap-3 p-5 sm:grid-cols-2 sm:p-6">
                        @foreach([
                            ['Admin / Super Admin hesapları', $protected['users'] ?? 0, 'ki-filled ki-shield-user'],
                            ['Roller', $protected['roles'] ?? 0, 'ki-filled ki-user-tick'],
                            ['Yetkiler', $protected['permissions'] ?? 0, 'ki-filled ki-key-square'],
                            ['Site, tema ve logo ayarları', $protected['site_settings'] ?? 0, 'ki-filled ki-design-1'],
                            ['Ödeme entegrasyonları', $protected['payment_integrations'] ?? 0, 'ki-filled ki-two-credit-cart'],
                            ['Panel menü tercihleri', 1, 'ki-filled ki-setting-4'],
                        ] as $item)
                            <div class="flex items-center gap-3 rounded-xl border border-border bg-muted/20 p-4">
                                <i class="{{ $item[2] }} text-lg text-success"></i>
                                <div class="min-w-0 flex-1 text-sm font-medium text-foreground">{{ $item[0] }}</div>
                                <span class="kt-badge kt-badge-sm kt-badge-light">{{ number_format((int) $item[1]) }}</span>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="kt-card">
                    <div class="kt-card-header min-h-0 py-5">
                        <div>
                            <h2 class="font-semibold text-foreground">Demo giriş bilgileri</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Üretilen uzman ve üye hesaplarının ortak şifresi.</p>
                        </div>
                    </div>
                    <div class="kt-card-content grid gap-4 p-5 sm:p-6">
                        <div class="rounded-xl border border-dashed border-primary/40 bg-primary/5 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Ortak demo şifresi</div>
                            <code class="mt-2 block text-lg font-semibold text-primary">{{ $demoPassword }}</code>
                        </div>

                        <div class="grid gap-2">
                            @forelse($demoAccounts as $account)
                                <div class="flex min-w-0 items-center gap-3 rounded-lg border border-border px-3 py-2.5">
                                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                        <i class="ki-filled ki-user"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-foreground">{{ $account->name }}</div>
                                        <div class="truncate text-xs text-muted-foreground">{{ $account->email }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-border p-5 text-center text-sm text-muted-foreground">
                                    Henüz demo hesabı üretilmedi.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </div>
@endsection
