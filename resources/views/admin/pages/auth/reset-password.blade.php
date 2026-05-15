@extends('admin.layouts.auth.base')

@section('content')
    <style>
        .page-bg {
            background-image: url('{{ asset('assets/media/images/2600x1200/bg-10.png') }}');
        }

        .dark .page-bg {
            background-image: url('{{ asset('assets/media/images/2600x1200/bg-10-dark.png') }}');
        }
    </style>

    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg px-4">
        <div class="kt-card w-full max-w-[480px]">
            <form
                method="POST"
                action="{{ route('password.update') }}"
                class="kt-card-content flex flex-col gap-5 p-8 sm:p-10"
                data-admin-reset-password-form="true"
            >
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="text-center">
                    <div class="mx-auto mb-5 flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <i class="ki-filled ki-lock-2 text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-semibold text-foreground">Yeni Şifre Belirle</h1>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        En az 8 karakter uzunluğunda güçlü bir şifre kullanın. İşlemden sonra yeni şifrenizle giriş yapabilirsiniz.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="kt-alert kt-alert-danger mb-5">
                        <div class="kt-alert-title">Hata</div>
                        <div class="kt-alert-text">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col gap-1">
                    <label for="admin_reset_email" class="kt-form-label font-normal text-mono">E-posta</label>
                    <input
                        id="admin_reset_email"
                        class="kt-input @error('email') border-danger @enderror"
                        type="email"
                        name="email"
                        value="{{ old('email', $email) }}"
                        placeholder="ornek@alanadi.com"
                        autocomplete="email"
                        required
                        autofocus
                    />
                    @error('email')
                        <div class="text-xs text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label for="admin_reset_password" class="kt-form-label font-normal text-mono">Yeni Şifre</label>
                        <input
                            id="admin_reset_password"
                            class="kt-input @error('password') border-danger @enderror"
                            type="password"
                            name="password"
                            placeholder="En az 8 karakter"
                            autocomplete="new-password"
                            data-admin-reset-password="true"
                            required
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="admin_reset_password_confirmation" class="kt-form-label font-normal text-mono">Şifre Tekrar</label>
                        <input
                            id="admin_reset_password_confirmation"
                            class="kt-input"
                            type="password"
                            name="password_confirmation"
                            placeholder="Şifreyi tekrar girin"
                            autocomplete="new-password"
                            data-admin-reset-password-confirmation="true"
                            required
                        />
                    </div>
                </div>

                <div class="rounded-lg border border-warning/30 bg-warning/10 px-3 py-2 text-xs text-warning hidden" data-admin-reset-password-message="true">
                    Şifre tekrarı yeni şifre ile aynı olmalı.
                </div>

                @error('password')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div>
                @enderror

                <button type="submit" class="kt-btn kt-btn-primary flex justify-center" data-admin-reset-password-submit="true">
                    Şifreyi Güncelle
                </button>

                <div class="text-center text-sm text-muted-foreground">
                    <a href="{{ route('login') }}" class="kt-link font-medium">Giriş ekranına dön</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('app_js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-admin-reset-password-form]');
            if (!form) return;

            const password = form.querySelector('[data-admin-reset-password]');
            const confirmation = form.querySelector('[data-admin-reset-password-confirmation]');
            const message = form.querySelector('[data-admin-reset-password-message]');
            const submit = form.querySelector('[data-admin-reset-password-submit]');

            const sync = () => {
                const hasMismatch = Boolean(password.value && confirmation.value && password.value !== confirmation.value);

                message?.classList.toggle('hidden', !hasMismatch);
                confirmation?.classList.toggle('border-warning', hasMismatch);
                if (submit) {
                    submit.disabled = hasMismatch;
                }
            };

            password?.addEventListener('input', sync);
            confirmation?.addEventListener('input', sync);
            sync();
        });
    </script>
@endpush
