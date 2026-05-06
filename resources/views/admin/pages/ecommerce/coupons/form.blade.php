@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[900px] grid gap-6">
        @includeIf('admin.partials._flash')

        <div class="flex items-end justify-between gap-4">
            <div>
                <span class="kt-badge kt-badge-sm kt-badge-light-primary">Kupon</span>
                <h1 class="mt-2 text-xl font-semibold text-foreground">{{ $pageTitle }}</h1>
            </div>
            <a href="{{ route('admin.ecommerce.coupons.index') }}" class="kt-btn kt-btn-light">Listeye Dön</a>
        </div>

        <form method="POST" action="{{ $coupon->exists ? route('admin.ecommerce.coupons.update', $coupon) : route('admin.ecommerce.coupons.store') }}" class="kt-card" data-native-submit="true">
            @csrf
            @if($coupon->exists)
                @method('PUT')
            @endif

            <div class="kt-card-content grid gap-5 p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Kupon Kodu</label>
                        <input name="code" class="kt-input uppercase" value="{{ old('code', $coupon->code) }}" placeholder="YENI10">
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Kupon Adı</label>
                        <input name="name" class="kt-input" value="{{ old('name', $coupon->name) }}" placeholder="Yeni müşteri indirimi">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Tip</label>
                        <select name="type" class="kt-select">
                            @foreach($typeOptions as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" @selected(old('type', $coupon->type) === $typeKey)>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Değer</label>
                        <input name="value" type="number" step="0.01" min="0" class="kt-input" value="{{ old('value', $coupon->value ?? 0) }}">
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Maksimum İndirim</label>
                        <input name="max_discount_total" type="number" step="0.01" min="0" class="kt-input" value="{{ old('max_discount_total', $coupon->max_discount_total) }}">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Minimum Sepet</label>
                        <input name="min_order_total" type="number" step="0.01" min="0" class="kt-input" value="{{ old('min_order_total', $coupon->min_order_total) }}">
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Toplam Kullanım Limiti</label>
                        <input name="usage_limit" type="number" min="1" class="kt-input" value="{{ old('usage_limit', $coupon->usage_limit) }}">
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Müşteri Başına Limit</label>
                        <input name="per_customer_limit" type="number" min="1" class="kt-input" value="{{ old('per_customer_limit', $coupon->per_customer_limit) }}">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Başlangıç</label>
                        <div class="kt-input w-full">
                            <i class="ki-outline ki-calendar"></i>
                            <input
                                name="starts_at"
                                class="grow"
                                type="text"
                                readonly
                                placeholder="GG.AA.YYYY SS:DD"
                                value="{{ old('starts_at', optional($coupon->starts_at)->format('d.m.Y H:i')) }}"
                                data-app-date-picker="true"
                                data-app-date-mode="datetime"
                                data-app-date-format="DD.MM.YYYY HH:mm"
                            >
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Bitiş</label>
                        <div class="kt-input w-full">
                            <i class="ki-outline ki-calendar"></i>
                            <input
                                name="ends_at"
                                class="grow"
                                type="text"
                                readonly
                                placeholder="GG.AA.YYYY SS:DD"
                                value="{{ old('ends_at', optional($coupon->ends_at)->format('d.m.Y H:i')) }}"
                                data-app-date-picker="true"
                                data-app-date-mode="datetime"
                                data-app-date-format="DD.MM.YYYY HH:mm"
                            >
                        </div>
                    </div>
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label">Uygulanacağı Ürün/Kategori Kodları</label>
                    <textarea name="applies_to_text" rows="3" class="kt-textarea" placeholder="SKU veya kategori kodlarını virgül ya da satır satır yazabilirsiniz.">{{ old('applies_to_text', implode(', ', $coupon->applies_to ?? [])) }}</textarea>
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label">Not</label>
                    <textarea name="notes" rows="4" class="kt-textarea">{{ old('notes', $coupon->notes) }}</textarea>
                </div>

                <label class="flex items-center gap-3 text-sm text-muted-foreground">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked(old('is_active', $coupon->is_active ?? true))>
                    Kupon aktif
                </label>
            </div>

            <div class="kt-card-footer justify-end">
                <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
@endsection
