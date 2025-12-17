@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex flex-col">
                    <h1 class="text-xl font-semibold">{{ $pageTitle ?? 'Kategori Düzenle' }}</h1>
                    <div class="text-sm text-muted-foreground">ID: {{ $category->id }} • Slug: {{ $category->slug }}</div>
                </div>

                <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-light">
                    Geri
                </a>
            </div>

            <div class="kt-card kt-card-grid min-w-full">
                <form method="POST" action="{{ route('admin.categories.update', ['category' => $category->id]) }}">
                    @csrf
                    @method('PUT')

                    <div class="kt-card-content p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                            {{-- LEFT --}}
                            <div class="lg:col-span-2 flex flex-col gap-6">

                                {{-- Name + Slug Auto (same row) --}}
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
                                    <div class="lg:col-span-2 flex flex-col gap-2">
                                        <label class="kt-form-label font-normal text-mono">Kategori Adı</label>
                                        <input
                                            id="cat_name"
                                            class="kt-input @error('name') kt-input-invalid @enderror"
                                            name="name"
                                            value="{{ old('name', $category->name) }}"
                                            required
                                        />
                                        @error('name') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-medium">Slug otomatik</span>
                                            <span class="text-sm text-muted-foreground">Açarsan ad→slug</span>
                                        </div>

                                        <label class="kt-switch kt-switch-sm">
                                            <input id="slug_auto" type="checkbox" class="kt-switch kt-switch-mono">
                                        </label>
                                    </div>
                                </div>

                                {{-- Slug + Regen (same row) --}}
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
                                    <div class="lg:col-span-2 flex flex-col gap-2">
                                        <label class="kt-form-label font-normal text-mono">Slug</label>
                                        <input
                                            id="cat_slug"
                                            class="kt-input @error('slug') kt-input-invalid @enderror"
                                            name="slug"
                                            value="{{ old('slug', $category->slug) }}"
                                            required
                                        />
                                        @error('slug') <div class="text-xs text-danger">{{ $message }}</div> @enderror

                                        <div class="text-sm text-muted-foreground">
                                            URL önizleme:
                                            <span class="font-medium">/kategori/</span><span id="slug_preview" class="font-medium"></span>
                                        </div>
                                    </div>

                                    <button type="button" id="slug_regen" class="kt-btn kt-btn-light">
                                        Yeniden üret
                                    </button>
                                </div>

                                {{-- Parent --}}
                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label font-normal text-mono">Üst Kategori</label>
                                    <select id="parent_id" name="parent_id" class="kt-input @error('parent_id') kt-input-invalid @enderror">
                                        <option value="">— Seçme —</option>
                                        @foreach(($parentOptions ?? []) as $opt)
                                            <option value="{{ $opt['id'] }}" @selected(old('parent_id', $category->parent_id) == $opt['id'])>
                                                {{ $opt['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id') <div class="text-xs text-danger">{{ $message }}</div> @enderror

                                    <div class="text-sm text-muted-foreground">
                                        Not: Kendi alt kategorilerini üst kategori olarak seçemezsin.
                                    </div>
                                </div>

                            </div>

                            {{-- RIGHT --}}
                            <div class="lg:col-span-1 flex flex-col gap-6">

                                <div class="rounded-xl border p-5">
                                    <div class="font-semibold mb-1">Dikkat</div>
                                    <div class="text-sm text-muted-foreground">
                                        Slug’ı değiştirirsen mevcut URL’ler kırılır. Sadece bilinçli yap.
                                    </div>
                                </div>

                                <div class="flex gap-2 justify-center">
                                    <button type="submit" class="kt-btn kt-btn-primary">Güncelle</button>
                                    <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-light">İptal</a>
                                </div>

                                @if(auth()->user()->hasPermission('category.delete'))
                                    <form method="POST"
                                          action="{{ route('admin.categories.destroy', ['category' => $category->id]) }}"
                                          onsubmit="return confirm('Kategori silinsin mi? (İlişkiler otomatik kaldırılır)')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="kt-btn kt-btn-danger w-full">Sil</button>
                                    </form>
                                @endif

                            </div>

                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection

@push('page_js')
    <script>
        (function () {
            const nameEl = document.getElementById('cat_name');
            const slugEl = document.getElementById('cat_slug');
            const autoEl = document.getElementById('slug_auto');
            const regenEl = document.getElementById('slug_regen');
            const previewEl = document.getElementById('slug_preview');
            const parentEl = document.getElementById('parent_id');

            function slugifyTR(str) {
                return String(str || '')
                    .trim()
                    .toLowerCase()
                    .replaceAll('ğ', 'g').replaceAll('ü', 'u').replaceAll('ş', 's')
                    .replaceAll('ı', 'i').replaceAll('ö', 'o').replaceAll('ç', 'c')
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }

            function syncPreview() {
                if (!previewEl) return;
                previewEl.textContent = (slugEl?.value || '').trim();
            }

            function setSlugFromName() {
                if (!nameEl || !slugEl) return;
                slugEl.value = slugifyTR(nameEl.value);
                syncPreview();
            }

            document.addEventListener('DOMContentLoaded', () => {
                syncPreview();

                // edit'te default kapalı; kullanıcı açarsa otomatik üret
                if (nameEl && autoEl) {
                    nameEl.addEventListener('input', () => {
                        if (!autoEl.checked) return;
                        setSlugFromName();
                    });
                }

                if (slugEl) slugEl.addEventListener('input', syncPreview);

                if (regenEl) {
                    regenEl.addEventListener('click', () => {
                        // bilinçli aksiyon: otomatik açık olmasa bile üretir
                        setSlugFromName();
                    });
                }

                if (parentEl && window.$ && $.fn && $.fn.select2) {
                    $(parentEl).select2({ width: '100%', placeholder: 'Üst kategori seç' });
                }
            });
        })();
    </script>
@endpush
