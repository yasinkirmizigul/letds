<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">

<div class="container mx-auto py-6 px-4">
    <div class="mb-6 rounded-3xl border border-border bg-white/90 px-5 py-4 shadow-sm">
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
