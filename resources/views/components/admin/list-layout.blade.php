{{-- views/admin/components/list-layout.blade.php --}}
<div class="kt-card kt-card-grid">
    <div class="kt-card-header py-4">
        <div class="flex flex-wrap items-center justify-between gap-3 w-full">
            <div class="flex flex-wrap items-center gap-3">
                <h3 class="kt-card-title">{{ $title }}</h3>

                <form method="GET" action="{{ $searchAction }}" class="flex flex-wrap items-center gap-3">
                    <input type="text"
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="Ara..."
                           class="kt-input h-9 w-[260px]"
                           autocomplete="off">

                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Sayfa başı</span>

                        <select name="perpage"
                                class="kt-select h-9 w-[90px]"
                                onchange="this.form.submit()">
                            @foreach([10,25,50,100,200] as $n)
                                <option value="{{ $n }}" {{ (int)request('perpage',25) === $n ? 'selected' : '' }}>
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        </div>
    </div>

    <div class="kt-card-content p-0">
        <div class="kt-scrollable-x-auto overflow-y-hidden">
            {{ $slot }}
        </div>
    </div>
</div>
