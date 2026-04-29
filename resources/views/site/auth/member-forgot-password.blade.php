@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_460px]">
            <section class="rounded-[36px] border border-border bg-white/90 p-8 shadow-sm">
                <div class="inline-flex rounded-full border border-primary/20 bg-primary/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-primary">
                    Şifre Yardımı
                </div>
                <h1 class="mt-5 text-4xl font-semibold leading-tight text-foreground">Üyelik hesabınız için güvenli şifre yenileme akışı.</h1>
                <p class="mt-5 max-w-3xl text-sm leading-8 text-muted-foreground">
                    E-posta adresinizi girin. Aktif bir üyelik hesabı varsa şifre yenileme bağlantısı tarafınıza gönderilir. Bağlantı süreli ve tek kullanımlık güvenlik tokenı ile çalışır.
                </p>

                <div class="mt-8 grid gap-4 md:grid-cols-3">
                    <div class="rounded-[28px] border border-border bg-muted/20 p-5">
                        <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">1. Adım</div>
                        <div class="mt-3 text-lg font-semibold text-foreground">E-postanı Gir</div>
                        <div class="mt-2 text-sm leading-7 text-muted-foreground">Kayıtlı e-posta adresinizi yazın.</div>
                    </div>
                    <div class="rounded-[28px] border border-border bg-muted/20 p-5">
                        <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">2. Adım</div>
                        <div class="mt-3 text-lg font-semibold text-foreground">Bağlantıyı Aç</div>
                        <div class="mt-2 text-sm leading-7 text-muted-foreground">E-postadaki güvenli bağlantı ile yeni şifre belirleyin.</div>
                    </div>
                    <div class="rounded-[28px] border border-border bg-muted/20 p-5">
                        <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">3. Adım</div>
                        <div class="mt-3 text-lg font-semibold text-foreground">Yeniden Giriş Yap</div>
                        <div class="mt-2 text-sm leading-7 text-muted-foreground">Yeni şifreniz ile üye paneline dönün.</div>
                    </div>
                </div>
            </section>

            <section class="rounded-[36px] border border-border bg-slate-950 p-6 text-white shadow-[0_24px_80px_rgba(15,23,42,0.18)] lg:p-8">
                <div class="mb-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.28em] text-white/60">Şifre Sıfırlama</div>
                    <h2 class="mt-2 text-3xl font-semibold">Şifremi Unuttum</h2>
                    <p class="mt-3 text-sm leading-7 text-white/70">
                        E-postanıza bir yenileme bağlantısı gönderelim.
                    </p>
                </div>

                @if(session('status'))
                    <div class="mb-5 rounded-2xl border border-primary/15 bg-primary/10 px-4 py-3 text-sm text-primary">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('member.password.email') }}" class="grid gap-5">
                    @csrf

                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-white" for="member_forgot_email">E-posta</label>
                        <input
                            id="member_forgot_email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="kt-input @error('email') kt-input-invalid @enderror"
                            placeholder="ornek@alanadi.com"
                            autocomplete="email"
                            required
                        >
                        @error('email')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="kt-btn kt-btn-primary w-full">Yenileme Bağlantısı Gönder</button>
                </form>

                <div class="mt-6 rounded-[28px] border border-white/10 bg-white/10 p-5 text-sm leading-7 text-white/70">
                    Şifrenizi hatırladıysanız giriş ekranına dönebilirsiniz.
                    <a href="{{ route('member.login', ['site_locale' => $siteCurrentLocale]) }}" class="ml-2 font-medium text-white underline underline-offset-4">Üye girişine git</a>
                </div>
            </section>
        </div>
    </div>
@endsection
