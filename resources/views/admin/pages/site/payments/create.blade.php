@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="site.payments.create">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">Ödeme Entegrasyonu Ekle</h1>
                    <div class="text-sm text-muted-foreground">
                        Sağlayıcı şablonunu seç, credential alanlarını doldur ve canlıya çıkmadan önce güvenlik kontrolünü tamamla.
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.site.payments.index') }}" class="kt-btn kt-btn-light">Listeye Dön</a>
        </div>

        @include('admin.pages.site.payments.partials._form', [
            'formAction' => route('admin.site.payments.store'),
            'formMethod' => 'POST',
        ])
    </div>
@endsection
