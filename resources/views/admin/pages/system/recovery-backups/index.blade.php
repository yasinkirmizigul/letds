@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[92%] space-y-6">
        @includeIf('admin.partials._flash')

        <div>
            <span class="kt-badge kt-badge-sm kt-badge-light-primary">Süper Admin</span>
            <h1 class="mt-3 text-2xl font-semibold text-foreground">Kurtarma yedekleri</h1>
            <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                Haftalık tam yedekler özel bulutta AES-256 ile şifreli tutulur. Bu ekran arşivi indirir veya buluta hazırlar;
                canlı veritabanını değiştirmez.
            </p>
        </div>

        @if($problems)
            <div class="kt-alert kt-alert-warning">
                <div class="kt-alert-text">
                    <strong>Yedekleme henüz hazır değil.</strong>
                    <ul class="mt-2 list-disc ps-5">
                        @foreach($problems as $problem)
                            <li>{{ $problem }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if($listError)
            <div class="kt-alert kt-alert-danger"><div class="kt-alert-text">{{ $listError }}</div></div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="kt-card">
                <div class="kt-card-header"><h2 class="kt-card-title">Otomatik yedek</h2></div>
                <div class="kt-card-content space-y-3 py-5 text-sm text-muted-foreground">
                    <p>Her pazartesi 04.15’te (uygulama saati) veritabanı, yüklenen dosyalar ve <code>.env</code> yedeklenir. Son 8 haftalık arşivler korunur.</p>
                    <p>Sunucuda zamanlayıcı çalışmalı: <code>* * * * * php artisan schedule:run</code></p>
                    <p>İlk yedeği konsolda <code>php artisan recovery:backup</code> ile oluşturun; bulut ve parola ayarları eksikse işlem güvenli biçimde durur.</p>
                </div>
            </section>

            <section class="kt-card">
                <div class="kt-card-header"><h2 class="kt-card-title">Şifreli arşiv içe aktar</h2></div>
                <div class="kt-card-content space-y-4 py-5">
                    <p class="text-sm text-muted-foreground">Aynı yedek parolasıyla açılabilen tam PROBABLUE arşivini özel buluta alın. Büyük arşivlerde PHP yükleme sınırı geçerliyse doğrudan S3 aktarımını kullanın.</p>
                    <form method="POST" action="{{ route('admin.recovery-backups.import') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" name="archive" accept=".zip,application/zip" required class="kt-input w-full" aria-label="Şifreli yedek arşivi">
                        @error('archive')<p class="text-sm text-destructive">{{ $message }}</p>@enderror
                        <button type="submit" class="kt-btn kt-btn-primary" @disabled($problems)>Doğrula ve buluta yükle</button>
                    </form>
                </div>
            </section>
        </div>

        <section class="kt-card">
            <div class="kt-card-header"><h2 class="kt-card-title">Buluttaki arşivler</h2></div>
            <div class="kt-card-content py-5">
                @forelse($archives as $archive)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border py-3 last:border-0">
                        <div class="min-w-0">
                            <div class="break-all font-medium text-foreground">{{ $archive['name'] }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ date('d.m.Y H:i', $archive['modified']) }} · {{ number_format($archive['size'] / 1048576, 1, ',', '.') }} MB
                            </div>
                        </div>
                        <a class="kt-btn kt-btn-outline" href="{{ route('admin.recovery-backups.download', ['file' => $archive['name']]) }}">İndir</a>
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground">Henüz yedek bulunamadı veya bulut bağlantısı hazır değil.</p>
                @endforelse
            </div>
        </section>

        <div class="kt-alert kt-alert-primary"><div class="kt-alert-text">
            Sunucu kaybında önce kodu ve bağımlılıkları kurun. Sonra <code>php artisan recovery:stage DOSYA.zip</code> ile
            <code>.env</code> ve yüklenen dosyaları güvenli klasöre çıkarın; veritabanını yalnızca yeni/boş veritabanına
            <code>php artisan recovery:restore-db DOSYA.zip</code> ile yükleyin.
            Ayrıntılı sıra için kurtarma kılavuzunu izleyin.
        </div></div>
    </div>
@endsection
