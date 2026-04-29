@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_500px]">
            <section class="rounded-[36px] border border-border bg-white/90 p-8 shadow-sm">
                <div class="inline-flex rounded-full border border-primary/20 bg-primary/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-primary">
                    Güvenli Yenileme
                </div>
                <h1 class="mt-5 text-4xl font-semibold leading-tight text-foreground">Yeni şifrenizi belirleyin ve üye paneline geri dönün.</h1>
                <p class="mt-5 max-w-3xl text-sm leading-8 text-muted-foreground">
                    Güçlü bir şifre belirleyin. Şifre güncellendiğinde mevcut oturum güvenlik yapısı yenilenir ve bir sonraki girişiniz yeni şifre ile gerçekleşir.
                </p>
            </section>

            <section class="rounded-[36px] border border-border bg-slate-950 p-6 text-white shadow-[0_24px_80px_rgba(15,23,42,0.18)] lg:p-8">
                <div class="mb-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.28em] text-white/60">Şifre Yenile</div>
                    <h2 class="mt-2 text-3xl font-semibold">Yeni Şifre Oluştur</h2>
                    <p class="mt-3 text-sm leading-7 text-white/70">
                        En az 8 karakter uzunluğunda, tahmin edilmesi zor bir şifre kullanın.
                    </p>
                </div>

                <form method="POST" action="{{ route('member.password.update') }}" class="grid gap-5">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-white" for="member_reset_email">E-posta</label>
                        <input
                            id="member_reset_email"
                            type="email"
                            name="email"
                            value="{{ old('email', $email) }}"
                            class="kt-input @error('email') kt-input-invalid @enderror"
                            placeholder="ornek@alanadi.com"
                            autocomplete="email"
                            required
                        >
                        @error('email')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium text-white" for="member_reset_password">Yeni Şifre</label>
                            <input
                                id="member_reset_password"
                                type="password"
                                name="password"
                                class="kt-input @error('password') kt-input-invalid @enderror"
                                placeholder="En az 8 karakter"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium text-white" for="member_reset_password_confirmation">Şifre Tekrar</label>
                            <input
                                id="member_reset_password_confirmation"
                                type="password"
                                name="password_confirmation"
                                class="kt-input"
                                placeholder="Yeni şifrenizi tekrar girin"
                                autocomplete="new-password"
                                required
                            >
                        </div>
                    </div>

                    @error('password')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror

                    <button type="submit" class="kt-btn kt-btn-primary w-full">Şifreyi Güncelle</button>
                </form>
            </section>
        </div>
    </div>
@endsection
