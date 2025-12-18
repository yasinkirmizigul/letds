@extends('admin.layouts.auth.base')

@section('content')
    <!-- Page -->
    <style>
        .page-bg {
            background-image: url('assets/media/images/2600x1200/bg-10.png');
        }

        .dark .page-bg {
            background-image: url('assets/media/images/2600x1200/bg-10-dark.png');
        }
    </style>
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="kt-card max-w-[370px] w-full">

            <form
                action="{{ route('login.post') }}"
                class="kt-card-content flex flex-col gap-5 p-10"
                id="sign_in_form"
                method="POST"
            >
                @csrf

                {{-- Genel hata kutusu (isteğe bağlı) --}}
                @if ($errors->any())
                    <div class="kt-alert kt-alert-danger">
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
                    <label class="kt-form-label font-normal text-mono">
                        E-posta
                    </label>
                    <input
                        class="kt-input @error('email') border-danger @enderror"
                        placeholder="email@email.com"
                        type="text"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    />
                    @error('email')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between gap-1">
                        <label class="kt-form-label font-normal text-mono">
                            Şifre
                        </label>

                        {{-- Şimdilik route yoksa # bırak --}}
                        <a class="text-sm kt-link shrink-0" href="#">
                            Şifremi Unuttum
                        </a>
                    </div>

                    <div class="kt-input @error('password') border-danger @enderror" data-kt-toggle-password="true">
                        <input
                            name="password"
                            placeholder="Enter Password"
                            type="password"
                            value=""
                            autocomplete="current-password"
                            required
                        />

                        <button
                            class="kt-btn kt-btn-sm kt-btn-mono kt-btn-icon bg-transparent! -me-1.5"
                            data-kt-toggle-password-trigger="true"
                            type="button"
                        >
                            <span class="kt-toggle-password-active:hidden">
                                <i class="ki-filled ki-eye text-muted-foreground"></i>
                            </span>
                            <span class="hidden kt-toggle-password-active:block">
                                <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                            </span>
                        </button>
                    </div>

                    @error('password')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <label class="kt-label">
                    <input
                        class="kt-checkbox kt-checkbox-sm"
                        name="remember"
                        type="checkbox"
                        value="1"
                        @checked(old('remember'))
                    />
                    <span class="kt-checkbox-label">
                        Beni Hatırla!
                    </span>
                </label>

                <button class="kt-btn kt-btn-primary flex justify-center grow" type="submit">
                    Giriş
                </button>

            </form>
        </div>
    </div>
@endsection
