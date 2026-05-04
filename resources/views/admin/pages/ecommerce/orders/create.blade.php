@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="ecommerce.orders.create">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Yeni Sipariş</span>
                <h1 class="mt-2 text-xl font-semibold text-foreground">Sipariş Oluştur</h1>
                <div class="text-sm text-muted-foreground">Ürün, müşteri, ödeme ve teslimat bilgilerini tek kayıt altında toplayın.</div>
            </div>
            <a href="{{ route('admin.ecommerce.orders.index') }}" class="kt-btn kt-btn-light">
                <i class="ki-filled ki-left"></i>
                Listeye Dön
            </a>
        </div>

        <form method="POST" action="{{ route('admin.ecommerce.orders.store') }}" data-ajax-redirect="true" data-ecommerce-order-form="true">
            @csrf
            @include('admin.pages.ecommerce.orders.partials._form')
        </form>
    </div>
@endsection
