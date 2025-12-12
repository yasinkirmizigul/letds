{{-- resources/views/layouts/app/partials/sidebar.blade.php --}}
<aside class="hidden lg:flex flex-col w-64 border-r bg-background/95 backdrop-blur">
    {{-- Logo / Brand --}}
    <div class="flex items-center h-14 px-4 border-b">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <img src="{{ asset('assets/media/logos/logo-default.svg') }}"
                 alt="Logo"
                 class="h-6 w-auto" />
            <span class="text-sm font-semibold tracking-tight">
                {{ config('app.name', 'Metronic Demo1') }}
            </span>
        </a>
    </div>

    {{-- Menu container (sen burada menüyü dolduracaksın) --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        {{-- Örnek placeholder; sonra kendi item’lerini koyarsın --}}
        {{--
        <a href="#" class="flex items-center px-3 py-2 rounded-lg text-sm
                           text-muted-foreground hover:text-foreground
                           hover:bg-muted transition">
            <i class="ki-outline ki-element-11 text-lg mr-2"></i>
            <span>Dashboard</span>
        </a>
        --}}
    </nav>
</aside>
