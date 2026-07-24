@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-10">
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">
            Güvenli Yenileme
        </div>
        <h1 class="mt-3 font-display text-3xl font-semibold leading-tight text-foreground md:text-4xl">Yeni şifrenizi belirleyin ve üye paneline geri dönün.</h1>
        <p class="mt-4 text-sm leading-8 text-muted-foreground">
            Güçlü bir şifre belirleyin. Şifre güncellendiğinde mevcut oturum güvenlik yapısı yenilenir ve bir sonraki girişiniz yeni şifre ile gerçekleşir.
        </p>

        <section class="mt-8 rounded-3xl border border-border bg-background p-6 shadow-sm lg:p-8">
            <form method="POST" action="{{ route('member.password.update') }}" class="grid gap-5">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="grid gap-2">
                    <label class="text-sm font-medium text-foreground" for="member_reset_email">E-posta</label>
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
                        <div class="mt-1.5 text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-foreground" for="member_reset_password">Yeni Şifre</label>
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
                        <label class="text-sm font-medium text-foreground" for="member_reset_password_confirmation">Şifre Tekrar</label>
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
                    <div class="mt-1.5 text-xs text-danger">{{ $message }}</div>
                @enderror

                <button type="submit" class="kt-btn kt-btn-primary w-full">Şifreyi Güncelle</button>
            </form>
        </section>
    </div>
@endsection
