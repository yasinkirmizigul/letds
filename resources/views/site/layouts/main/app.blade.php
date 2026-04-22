<!DOCTYPE html>
<html lang="tr" data-kt-theme="true" data-kt-theme-mode="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <script>
        (function () {
            let themeMode = localStorage.getItem('kt-theme') || 'system';

            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.classList.remove('light', 'dark');
            document.documentElement.classList.add(themeMode);
            document.documentElement.setAttribute('data-kt-theme-mode', themeMode);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background text-foreground">

<div class="container mx-auto py-6 px-4">
    <div class="mb-6 rounded-3xl app-shell-surface px-5 py-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.24em] text-primary">Letds</div>
                <h2 class="mt-1 text-xl font-semibold text-foreground">İletişim ve Randevu Merkezi</h2>
                <p class="text-sm text-muted-foreground">
                    Üyeler ve ziyaretçiler doğru kullanıcıya kolayca mesaj bırakabilir, üyeler randevu akışını da buradan yönetebilir.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('site.contact-messages.create') }}"
                    class="kt-btn kt-btn-sm {{ request()->routeIs('site.contact-messages.*') ? 'kt-btn-primary' : 'kt-btn-light' }}"
                >
                    Mesaj Gönder
                </a>

                <a
                    href="{{ route('member.appointments.index') }}"
                    class="kt-btn kt-btn-sm {{ request()->routeIs('member.appointments.*') ? 'kt-btn-primary' : 'kt-btn-light' }}"
                >
                    Randevu Al
                </a>

                @auth('member')
                    <span class="rounded-full border border-border bg-muted/20 px-4 py-2 text-sm font-medium text-foreground">
                        {{ auth('member')->user()->full_name }}
                    </span>

                    <form method="POST" action="{{ route('member.logout') }}">
                        @csrf
                        <button class="kt-btn kt-btn-sm kt-btn-light">Çıkış</button>
                    </form>
                @else
                    <a href="{{ route('member.login') }}" class="kt-btn kt-btn-sm kt-btn-light">
                        Üye Girişi
                    </a>
                @endauth
            </div>
        </div>
    </div>

    @yield('content')
</div>

</body>
</html>
