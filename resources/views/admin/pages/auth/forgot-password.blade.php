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
        <div class="kt-card w-full max-w-[430px]">
            <form method="POST" action="{{ route('password.email') }}" class="kt-card-content flex flex-col gap-5 p-8 sm:p-10">
                @csrf

                <div class="text-center">
                    <div class="mx-auto mb-5 flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <i class="ki-filled ki-sms text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-semibold text-foreground">Şifremi Unuttum</h1>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Yönetim paneli hesabınıza ait e-posta adresini yazın. Hesap uygunsa yenileme bağlantısı gönderilir.
                    </p>
                </div>

                @if(session('status'))
                    <div class="kt-alert kt-alert-success mb-5">
                        <div class="kt-alert-text">{{ session('status') }}</div>
                    </div>
                @endif

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
                    <label for="admin_forgot_email" class="kt-form-label font-normal text-mono">E-posta</label>
                    <input
                        id="admin_forgot_email"
                        class="kt-input @error('email') border-danger @enderror"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="ornek@alanadi.com"
                        autocomplete="email"
                        required
                        autofocus
                    />
                    @error('email')
                        <div class="text-xs text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="kt-btn kt-btn-primary flex justify-center">
                    Yenileme Bağlantısı Gönder
                </button>

                <div class="text-center text-sm text-muted-foreground">
                    Şifrenizi hatırladınız mı?
                    <a href="{{ route('login') }}" class="kt-link font-medium">Giriş ekranına dön</a>
                </div>
            </form>
        </div>
    </div>
@endsection
