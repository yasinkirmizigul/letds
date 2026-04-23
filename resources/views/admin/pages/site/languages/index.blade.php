@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="site.languages.index">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">Dil Yönetimi</h1>
                    <div class="text-sm text-muted-foreground">
                        Siteyi çok dilli kurgula, aktif dilleri yönet ve varsayılan dili gerektiğinde güvenli şekilde değiştir.
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam Dil</div>
                <div class="mt-2 text-3xl font-semibold">{{ $stats['all'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Aktif</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">RTL</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['rtl'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Varsayılan</div>
                <div class="mt-2 text-2xl font-semibold text-primary">{{ $stats['default']?->native_name ?: '-' }}</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Yeni Dil Ekle</h3>
                        <div class="text-sm text-muted-foreground">Örnek kodlar: `en`, `de`, `ar`, `en-US`.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.site.languages.store') }}" class="kt-card-content grid gap-4 p-6">
                    @csrf

                    <div class="grid gap-2">
                        <label class="kt-form-label">Dil Kodu</label>
                        <input name="code" class="kt-input @error('code') kt-input-invalid @enderror" value="{{ old('code') }}" placeholder="en">
                        @error('code')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">İngilizce Adı</label>
                        <input name="name" class="kt-input @error('name') kt-input-invalid @enderror" value="{{ old('name') }}" placeholder="English">
                        @error('name')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Yerel Adı</label>
                        <input name="native_name" class="kt-input @error('native_name') kt-input-invalid @enderror" value="{{ old('native_name') }}" placeholder="English / Deutsch / العربية">
                        @error('native_name')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Sıralama</label>
                        <input type="number" name="sort_order" class="kt-input @error('sort_order') kt-input-invalid @enderror" value="{{ old('sort_order', ($languages->max('sort_order') ?? 0) + 1) }}" min="0">
                        @error('sort_order')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl app-surface-card app-surface-card--soft p-4">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="kt-checkbox mt-1" @checked(old('is_active', true))>
                        <span>
                            <span class="block font-medium text-foreground">Aktif olarak ekle</span>
                            <span class="text-sm text-muted-foreground">Pasif diller ön yüzde seçilebilir görünmez.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl app-surface-card app-surface-card--soft p-4">
                        <input type="hidden" name="is_rtl" value="0">
                        <input type="checkbox" name="is_rtl" value="1" class="kt-checkbox mt-1" @checked(old('is_rtl'))>
                        <span>
                            <span class="block font-medium text-foreground">RTL düzen kullan</span>
                            <span class="text-sm text-muted-foreground">Arapça gibi sağdan sola diller için işaretle.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl app-surface-card app-surface-card--soft p-4">
                        <input type="hidden" name="make_default" value="0">
                        <input type="checkbox" name="make_default" value="1" class="kt-checkbox mt-1" @checked(old('make_default'))>
                        <span>
                            <span class="block font-medium text-foreground">Varsayılan dil yap</span>
                            <span class="text-sm text-muted-foreground">Gerekirse mevcut içerikler yeni varsayılan dile terfi ettirilir.</span>
                        </span>
                    </label>

                    <button type="submit" class="kt-btn kt-btn-primary">Dili Kaydet</button>
                </form>
            </div>

            <div class="grid gap-4">
                @foreach($languages as $language)
                    <div class="rounded-[28px] app-surface-card p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="grid gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-lg font-semibold text-foreground">{{ $language->native_name }}</div>
                                    <span class="kt-badge kt-badge-sm kt-badge-light">{{ $language->code }}</span>
                                    <span class="kt-badge kt-badge-sm {{ $language->is_active ? 'kt-badge-light-success' : 'kt-badge-light' }}">
                                        {{ $language->is_active ? 'Aktif' : 'Pasif' }}
                                    </span>
                                    @if($language->is_default)
                                        <span class="kt-badge kt-badge-sm kt-badge-light-primary">Varsayılan</span>
                                    @endif
                                    @if($language->is_rtl)
                                        <span class="kt-badge kt-badge-sm kt-badge-light-warning">RTL</span>
                                    @endif
                                </div>
                                <div class="text-sm text-muted-foreground">{{ $language->name }} • Sıra {{ $language->sort_order }}</div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if(!$language->is_default)
                                    <form method="POST" action="{{ route('admin.site.languages.makeDefault', $language) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="kt-btn kt-btn-light-primary">Varsayılan Yap</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.site.languages.toggleActive', $language) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="kt-btn kt-btn-light">
                                        {{ $language->is_active ? 'Pasifleştir' : 'Aktifleştir' }}
                                    </button>
                                </form>

                                @if(!$language->is_default)
                                    <form method="POST" action="{{ route('admin.site.languages.destroy', $language) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="kt-btn kt-btn-danger" onclick="return confirm('Bu dil silinsin mi? İlgili çeviriler de kaldırılacaktır.')">
                                            Sil
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <details class="mt-4 rounded-3xl app-surface-card app-surface-card--soft p-4">
                            <summary class="cursor-pointer list-none text-sm font-medium text-foreground">Detayları düzenle</summary>

                            <form method="POST" action="{{ route('admin.site.languages.update', $language) }}" class="mt-4 grid gap-4">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Dil Kodu</label>
                                        <input name="code" class="kt-input" value="{{ $language->code }}">
                                    </div>
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Sıralama</label>
                                        <input type="number" name="sort_order" class="kt-input" value="{{ $language->sort_order }}" min="0">
                                    </div>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">İngilizce Adı</label>
                                        <input name="name" class="kt-input" value="{{ $language->name }}">
                                    </div>
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Yerel Adı</label>
                                        <input name="native_name" class="kt-input" value="{{ $language->native_name }}">
                                    </div>
                                </div>

                                <div class="grid gap-3 lg:grid-cols-3">
                                    <label class="flex items-center gap-3 rounded-2xl bg-background px-4 py-3">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked($language->is_active)>
                                        <span class="text-sm text-muted-foreground">Aktif</span>
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl bg-background px-4 py-3">
                                        <input type="hidden" name="is_rtl" value="0">
                                        <input type="checkbox" name="is_rtl" value="1" class="kt-checkbox" @checked($language->is_rtl)>
                                        <span class="text-sm text-muted-foreground">RTL</span>
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl bg-background px-4 py-3">
                                        <input type="hidden" name="make_default" value="0">
                                        <input type="checkbox" name="make_default" value="1" class="kt-checkbox">
                                        <span class="text-sm text-muted-foreground">Varsayılan yap</span>
                                    </label>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="kt-btn kt-btn-light-primary">Güncelle</button>
                                </div>
                            </form>
                        </details>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
