@extends('admin.layouts.main.app')

@section('content')
    @php($isTrash = ($mode ?? 'active') === 'trash')

    <div class="kt-container-fixed"
         data-page="galleries.index"
         data-mode="{{ $isTrash ? 'trash' : 'active' }}">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold">
                        {{ $isTrash ? 'Galeriler - Çöp Kutusu' : 'Galeriler' }}
                    </h1>
                    <div class="text-sm text-muted-foreground">
                        Galeri oluştur, düzenle, içeriklere bağla.
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if(!$isTrash)
                        <a href="{{ route('admin.galleries.create') }}" class="kt-btn kt-btn-primary">
                            <i class="ki-outline ki-plus"></i> Yeni Galeri
                        </a>
                        <a href="{{ route('admin.galleries.trash') }}" class="kt-btn kt-btn-light">
                            <i class="ki-outline ki-trash"></i> Çöp
                        </a>
                    @else
                        <a href="{{ route('admin.galleries.index') }}" class="kt-btn kt-btn-light">
                            <i class="ki-outline ki-archive"></i> Aktif
                        </a>
                    @endif

                    <button type="button" id="galleriesRefresh" class="kt-btn kt-btn-light">
                        <i class="ki-outline ki-arrows-circle"></i> Yenile
                    </button>
                </div>
            </div>

            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex items-center gap-3 grow">
                        <div class="flex flex-row kt-input-icon w-full max-w-[420px]">
                            <i class="items-center ki-magnifier ki-outline me-2"></i>
                            <input id="galleriesSearch"
                                   type="text"
                                   class="kt-input"
                                   placeholder="Ara (isim / slug)">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <span id="galleriesInfo" class="text-sm text-muted-foreground"></span>
                        <div id="galleriesPagination" class="kt-datatable-pagination"></div>
                    </div>
                </div>

                <div class="kt-card-content p-5">
                    <div id="galleriesEmpty"
                         class="hidden text-sm text-muted-foreground">
                        Kayıt yok.
                    </div>

                    <div id="galleriesList" class="grid gap-3">
                        {{-- JS doldurur --}}
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
