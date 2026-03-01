@extends('admin.layouts.main.app')

@section('content')
    @php
        $currentStatus = old('status', $product?->status ?? \App\Models\Admin\Product\Product::STATUS_APPOINTMENT_PENDING);
        $currentFeatured = (bool) old('is_featured', (bool)($product?->is_featured ?? false));
        $st = $statusOptions[$currentStatus] ?? $statusOptions[\App\Models\Admin\Product\Product::STATUS_APPOINTMENT_PENDING];
    @endphp

    <div class="kt-container-fixed"
         data-page="products.edit"
         data-id="{{ $product->id }}"
         data-upload-url="{{ Route::has('admin.tinymce.upload') ? route('admin.tinymce.upload') : url('/admin/tinymce/upload') }}"
         data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
         data-tinymce-base="{{ url('/assets/vendors/tinymce') }}"
         data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
         data-status-options='@json($statusOptions)'>

        @includeIf('admin.partials._flash')

        <form method="POST"
              action="{{ route('admin.products.update', $product) }}"
              class="grid gap-5 lg:gap-7.5"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.pages.products.partials._form', [
                'product' => $product,
                'categories' => $categories ?? collect(),
                'selectedCategoryIds' => $selectedCategoryIds ?? [],
                'featuredMediaId' => $featuredMediaId ?? null,
            ])

            {{-- ✅ Durum + Anasayfa --}}
            <div class="kt-card">
                <div class="kt-card-header py-4">
                    <h3 class="kt-card-title">Durum &amp; Anasayfa</h3>
                </div>

                <div class="kt-card-body p-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">

                        {{-- Sol kolon: Durum --}}
                        <div class="grid gap-2">
                            <label for="status" class="kt-label">Durum</label>

                            <div class="flex items-center gap-3">
                                <select id="status"
                                        name="status"
                                        class="kt-select w-full"
                                        data-kt-select="true"
                                        data-kt-select-placeholder="Durum"
                                        data-status-select>
                                    @foreach($statusOptions as $key => $opt)
                                        <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                </select>

                                <span id="status_badge_preview"
                                      class="{{ $st['badge'] }} whitespace-nowrap items-center px-2 min-w-[130px] truncate"
                                      data-status-badge>
                        {{ $st['label'] }}
                    </span>
                            </div>

                            @error('status')
                            <div class="kt-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Sağ kolon: Anasayfa --}}
                        <div class="grid gap-2">
                            <label class="kt-label">Anasayfada göster</label>

                            <div class="flex items-center gap-3">
                                <input type="hidden" name="is_featured" value="0" />
                                <input type="checkbox"
                                       id="is_featured"
                                       class="kt-switch"
                                       name="is_featured"
                                       value="1"
                                       data-featured-toggle
                                    {{ $currentFeatured ? 'checked' : '' }}>

                                <span
                                    class="js-featured-label kt-badge kt-badge-sm transition-all duration-200
                                       {{ $currentFeatured ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}"
                                    aria-live="polite"
                                >
                                    {{ $currentFeatured ? 'Anasayfada' : 'Kapalı' }}
                                </span>
                            </div>

                            <div class="text-xs text-muted-foreground">
                                En fazla 5 ürün aynı anda anasayfada görünebilir.
                            </div>

                            @error('is_featured')
                            <div class="kt-error">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <button type="button" id="productDeleteBtn" class="kt-btn kt-btn-danger">Sil</button>

                <div class="flex items-center gap-3">
                    <button type="submit" class="kt-btn kt-btn-primary">Güncelle</button>
                    <a href="{{ route('admin.products.index') }}" class="kt-btn kt-btn-light">Geri</a>
                </div>
            </div>
        </form>
    </div>

    @include('admin.pages.media.partials._upload-modal')
@endsection
