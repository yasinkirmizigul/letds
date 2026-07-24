@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_480px]">
            <section class="overflow-hidden rounded-3xl border border-border bg-foreground text-background shadow-sm">
                <div class="flex h-full flex-col justify-between gap-10 p-8 lg:p-10">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">
                            Üye Alanı
                        </div>
                        <h1 class="mt-5 font-display text-3xl font-semibold leading-tight md:text-4xl">Randevu, mesaj ve hesap süreçlerini tek panelden yönetin.</h1>
                        <p class="mt-5 max-w-2xl text-sm leading-8 text-background/75">
                            Üyelik hesabınız ile randevu paneline erişebilir, geçmiş kayıtlarınızı takip edebilir ve size özel akışları güvenli şekilde kullanabilirsiniz.
                        </p>
                    </div>

                    <div class="grid gap-6 border-t border-background/15 pt-6 md:grid-cols-2">
                        <div>
                            <div class="text-xs uppercase tracking-[0.18em] text-background/60">Avantaj</div>
                            <div class="mt-3 text-lg font-semibold">Hızlı Erişim</div>
                            <div class="mt-2 text-sm leading-7 text-background/70">Randevu panelinize doğrudan ulaşıp işlemlerinizi birkaç adımda tamamlayın.</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-[0.18em] text-background/60">Güvenlik</div>
                            <div class="mt-3 text-lg font-semibold">Yetkili Kullanım</div>
                            <div class="mt-2 text-sm leading-7 text-background/70">Askıya alınan veya sonlandırılan üyeliklerde sistem net geri bildirim verir.</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-border bg-background p-6 shadow-sm lg:p-8">
                <div class="mb-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Giriş</div>
                    <h2 class="mt-2 font-display text-3xl font-semibold text-foreground md:text-4xl">Üye Girişi</h2>
                    <p class="mt-3 text-sm leading-7 text-muted-foreground">
                        Hesabınızla giriş yapın. Henüz hesabınız yoksa birkaç dakika içinde yeni bir üyelik oluşturabilirsiniz.
                    </p>
                </div>

                @if(session('success'))
                    <div class="mb-5 rounded-2xl border border-success/25 bg-success/10 px-4 py-3 text-sm text-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('status'))
                    <div class="mb-5 rounded-2xl border border-primary/25 bg-primary/10 px-4 py-3 text-sm text-primary">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('member.login.post') }}" class="grid gap-5">
                    @csrf

                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-foreground" for="member_login_email">E-posta</label>
                        <input
                            id="member_login_email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="kt-input @error('email') kt-input-invalid @enderror"
                            placeholder="ornek@alanadi.com"
                            autocomplete="email"
                            required
                        >
                        @error('email')
                            <div class="mt-1.5 text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="text-sm font-medium text-foreground" for="member_login_password">Şifre</label>
                            <a href="{{ route('member.password.request', ['site_locale' => $siteCurrentLocale]) }}" class="text-xs font-medium text-primary hover:underline">
                                Şifremi unuttum
                            </a>
                        </div>
                        <input
                            id="member_login_password"
                            type="password"
                            name="password"
                            class="kt-input @error('password') kt-input-invalid @enderror"
                            placeholder="Şifrenizi girin"
                            autocomplete="current-password"
                            required
                        >
                        @error('password')
                            <div class="mt-1.5 text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-border bg-muted/20 px-4 py-3 text-sm text-muted-foreground">
                        <input type="checkbox" name="remember" value="1" class="kt-checkbox" @checked(old('remember'))>
                        Bu cihazda oturumumu açık tut
                    </label>

                    <button type="submit" class="kt-btn kt-btn-primary w-full">Giriş Yap</button>
                </form>

                <div class="mt-6 rounded-2xl border border-border bg-muted/20 p-5">
                    <div class="text-sm font-semibold text-foreground">Yeni üyelik oluştur</div>
                    <div class="mt-2 text-sm leading-7 text-muted-foreground">
                        Profil bilgilerinizi, iletişim bilgilerinizi ve varsa belge ekinizi yükleyerek yeni hesabınızı hemen oluşturabilirsiniz.
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('member.register', ['site_locale' => $siteCurrentLocale]) }}" class="inline-flex kt-btn kt-btn-light">Üye Kayıt Sayfasına Git</a>
                        <a href="{{ route('member.terms.show', ['site_locale' => $siteCurrentLocale]) }}" class="inline-flex kt-btn kt-btn-light">Üyelik Bilgilendirmesini Oku</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
