@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="site.payments.edit">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">{{ $paymentIntegration->title }}</h1>
                    <div class="text-sm text-muted-foreground">
                        Secret alanları ekranda gösterilmez. Boş bırakırsan mevcut değer korunur.
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.site.payments.index') }}" class="kt-btn kt-btn-light">Listeye Dön</a>
                <form method="POST" action="{{ route('admin.site.payments.destroy', $paymentIntegration) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="kt-btn kt-btn-danger" onclick="return confirm('Bu ödeme entegrasyonu silinsin mi?')">
                        Entegrasyonu Sil
                    </button>
                </form>
            </div>
        </div>

        @include('admin.pages.site.payments.partials._form', [
            'formAction' => route('admin.site.payments.update', $paymentIntegration),
            'formMethod' => 'PUT',
        ])
    </div>
@endsection
