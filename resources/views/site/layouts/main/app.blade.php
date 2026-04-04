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

    {{-- basit header --}}
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-lg font-semibold">Randevu Sistemi</h2>

        @auth('member')
            <form method="POST" action="{{ route('member.logout') }}">
                @csrf
                <button class="btn btn-light">Çıkış</button>
            </form>
        @endauth
    </div>

    {{-- içerik --}}
    @yield('content')

</div>

</body>
</html>
