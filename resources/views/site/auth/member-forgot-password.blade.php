@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-10">
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">
            Şifre Yardımı
        </div>
        <h1 class="mt-3 font-display text-3xl font-semibold leading-tight text-foreground md:text-4xl">Üyelik hesabınız için güvenli şifre yenileme akışı.</h1>
        <p class="mt-4 text-sm leading-8 text-muted-foreground">
            E-posta adresinizi girin. Aktif bir üyelik hesabı varsa şifre yenileme bağlantısı tarafınıza gönderilir. Bağlantı süreli ve tek kullanımlık güvenlik tokenı ile çalışır.
        </p>

        <section class="mt-8 rounded-3xl border border-border bg-background p-6 shadow-sm lg:p-8">
            @if(session('status'))
                <div class="mb-5 rounded-2xl border border-primary/25 bg-primary/10 px-4 py-3 text-sm text-primary">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('member.password.email') }}" class="grid gap-5">
                @csrf

                <div class="grid gap-2">
                    <label class="text-sm font-medium text-foreground" for="member_forgot_email">E-posta</label>
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
                        <div class="mt-1.5 text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="kt-btn kt-btn-primary w-full">Yenileme Bağlantısı Gönder</button>
            </form>
        </section>

        <div class="mt-6 text-sm leading-7 text-muted-foreground">
            Şifrenizi hatırladıysanız giriş ekranına dönebilirsiniz.
            <a href="{{ route('member.login', ['site_locale' => $siteCurrentLocale]) }}" class="ml-1 font-medium text-primary hover:underline">Üye girişine git</a>
        </div>
    </div>
@endsection
