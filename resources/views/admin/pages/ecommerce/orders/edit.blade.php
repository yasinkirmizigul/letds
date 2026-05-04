@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="ecommerce.orders.edit">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                <h1 class="mt-2 text-xl font-semibold text-foreground">{{ $order->order_number }} Siparişini Düzenle</h1>
                <div class="text-sm text-muted-foreground">Sipariş satırlarını, müşteri bilgilerini ve operasyon durumlarını güncelleyin.</div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-eye"></i>
                    Detay
                </a>
                <a href="{{ route('admin.ecommerce.orders.index') }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-left"></i>
                    Listeye Dön
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.ecommerce.orders.update', $order) }}" data-ajax-redirect="true" data-ecommerce-order-form="true">
            @csrf
            @method('PUT')
            @include('admin.pages.ecommerce.orders.partials._form')
        </form>
    </div>
@endsection
