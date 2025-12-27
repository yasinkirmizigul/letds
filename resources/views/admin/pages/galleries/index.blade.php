@extends('admin.layouts.main.app')

@section('content')
    @php($isTrash = ($mode ?? 'active') === 'trash')

    <div class="kt-container-fixed max-w-[90%]"
         data-page="galleries.index"
         data-mode="{{ $mode ?? 'active' }}">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="kt-card">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">{{ $isTrash ? 'Galeriler - Çöp Kutusu' : 'Galeriler' }}</h3>
                        <div class="text-sm text-muted-foreground">Galeri oluştur, düzenle, içeriklere bağla.</div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if(!$isTrash)
                            <a class="kt-btn kt-btn-primary" href="{{ route('admin.galleries.create') }}">
                                <i class="ki-outline ki-plus"></i> Yeni Galeri
                            </a>
                            <a class="kt-btn kt-btn-light" href="{{ route('admin.galleries.trash') }}">Çöp</a>
                        @else
                            <a class="kt-btn kt-btn-light" href="{{ route('admin.galleries.index') }}">Aktif</a>
                        @endif
                    </div>
                </div>

                <div class="kt-card-content p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <input class="kt-input w-80" id="galleriesSearch" placeholder="Ara: isim / slug">
                        <button class="kt-btn kt-btn-light" id="galleriesRefresh">Yenile</button>
                    </div>

                    <div id="galleriesEmpty" class="text-sm text-muted-foreground hidden">Kayıt yok.</div>
                    <div id="galleriesList" class="flex flex-col gap-2"></div>

                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium mt-6">
                        <span id="galleriesInfo"></span>
                        <div class="kt-datatable-pagination" id="galleriesPagination"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
