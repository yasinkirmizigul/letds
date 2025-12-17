@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6" data-category-id="{{ $category->id }}">

        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">{{ $pageTitle ?? 'Kategori Düzenle' }}</h1>
                <div class="text-sm text-muted-foreground">ID: {{ $category->id }}</div>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-light">Geri</a>
        </div>

        <div class="kt-card">
            <form id="category_form"
                  method="POST"
                  action="{{ route('admin.categories.update', ['category' => $category->id]) }}"
                  class="kt-card-content p-8 flex flex-col gap-6"
                  data-slug-ok="1">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- NAME --}}
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <label class="kt-form-label font-normal text-mono mb-0">Kategori Adı</label>

                            <label class="inline-flex items-center gap-2 select-none">
                                <span class="text-sm text-muted-foreground">Slug otomatik</span>
                                <input id="slug_auto_toggle" type="checkbox" class="kt-switch kt-switch-mono">
                            </label>
                        </div>

                        <input id="cat_name"
                               name="name"
                               class="kt-input @error('name') kt-input-invalid @enderror"
                               value="{{ old('name', $category->name) }}"
                               required>

                        @error('name') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>

                    {{-- SLUG --}}
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="kt-form-label font-normal text-mono mb-0">Slug</label>

                            <div class="flex items-center gap-2">
                                <span id="slug_mode_badge" class="kt-badge kt-badge-light hidden">Manuel</span>

                                <button type="button" id="slug_regen_btn" class="kt-btn kt-btn-light kt-btn-sm">
                                    Başlıktan yeniden üret
                                </button>
                            </div>
                        </div>

                        <input id="cat_slug"
                               name="slug"
                               class="kt-input @error('slug') kt-input-invalid @enderror"
                               value="{{ old('slug', $category->slug) }}"
                               required>

                        @error('slug') <div class="text-xs text-danger">{{ $message }}</div> @enderror

                        <div class="text-sm text-muted-foreground">
                            URL Önizleme:
                            <span class="font-medium">{{ url('/category') }}/<span id="url_slug_preview">{{ old('slug', $category->slug) }}</span></span>
                        </div>

                        <div id="slug_status" class="text-xs mt-1 hidden"></div>
                    </div>

                    {{-- PARENT --}}
                    <div class="lg:col-span-2 flex flex-col gap-2">
                        <label class="kt-form-label font-normal text-mono">Üst Kategori (Opsiyonel)</label>

                        <select name="parent_id" class="kt-input @error('parent_id') kt-input-invalid @enderror">
                            <option value="">— Seçme —</option>
                            @foreach(($parentOptions ?? []) as $opt)
                                <option value="{{ $opt['id'] }}" @selected(old('parent_id', $category->parent_id ?? null) == $opt['id'])>
                                    {{ $opt['label'] }}
                                </option>
                            @endforeach
                        </select>


                        @error('parent_id') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>

                </div>

                <div class="flex justify-center gap-2 pt-2">
                    <button class="kt-btn kt-btn-primary">Güncelle</button>
                    <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-light">İptal</a>
                </div>
            </form>
        </div>

    </div>
@endsection

@push('page_js')
    <script>
        (function(){
            const form = document.getElementById('category_form');

            const nameInput = document.getElementById('cat_name');
            const slugInput = document.getElementById('cat_slug');
            const toggle = document.getElementById('slug_auto_toggle');
            const regenBtn = document.getElementById('slug_regen_btn');
            const badge = document.getElementById('slug_mode_badge');
            const preview = document.getElementById('url_slug_preview');
            const status = document.getElementById('slug_status');

            const ignoreId = document.querySelector('[data-category-id]')?.getAttribute('data-category-id');

            function slugifyTR(str) {
                return String(str)
                    .trim()
                    .toLowerCase()
                    .replaceAll('ğ','g').replaceAll('ü','u').replaceAll('ş','s')
                    .replaceAll('ı','i').replaceAll('ö','o').replaceAll('ç','c')
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }

            function syncPreview() {
                if (preview) preview.textContent = (slugInput?.value || '').trim();
            }

            function setManualMode(isManual) {
                if (badge) badge.classList.toggle('hidden', !isManual);

                if (slugInput) {
                    slugInput.style.boxShadow = isManual ? '0 0 0 2px rgba(245, 158, 11, .35)' : '';
                }

                if (regenBtn) regenBtn.classList.toggle('opacity-60', !isManual);
            }

            function applyAutoSlug() {
                if (!nameInput || !slugInput) return;
                slugInput.value = slugifyTR(nameInput.value);
                syncPreview();
            }

            // --- LIVE SLUG CHECK ---
            let t = null;

            function setStatus(ok, text) {
                if (!status) return;
                status.classList.remove('hidden');
                status.classList.toggle('text-success', ok);
                status.classList.toggle('text-danger', !ok);
                status.textContent = text;
                if (form) form.dataset.slugOk = ok ? '1' : '0';
            }

            async function checkSlug() {
                const v = (slugInput?.value || '').trim();
                if (!v) return setStatus(false, 'Slug boş olamaz.');

                const url = new URL("{{ route('admin.categories.checkSlug') }}", window.location.origin);
                url.searchParams.set('slug', v);
                if (ignoreId) url.searchParams.set('ignore', ignoreId);

                const r = await fetch(url.toString(), { headers: { 'Accept':'application/json' }});
                const j = await r.json();

                if (!j.ok) return setStatus(false, j.message || 'Kontrol edilemedi.');
                setStatus(!!j.available, j.message);
            }

            function scheduleCheck() {
                clearTimeout(t);
                t = setTimeout(() => checkSlug().catch(()=>{}), 250);
            }

            // --- EVENTS ---
            if (toggle && nameInput && slugInput) {
                // editte default: otomatik kapalı başlasın (slug dolu gelir). Manuel mod göster.
                toggle.checked = false;
                setManualMode(true);
                syncPreview();
                scheduleCheck();

                toggle.addEventListener('change', () => {
                    const isAuto = toggle.checked;
                    setManualMode(!isAuto);
                    if (isAuto) { applyAutoSlug(); scheduleCheck(); }
                });

                nameInput.addEventListener('input', () => {
                    if (!toggle.checked) return;
                    applyAutoSlug();
                    scheduleCheck();
                });

                slugInput.addEventListener('input', () => {
                    if (toggle.checked) {
                        toggle.checked = false;
                        setManualMode(true);
                    }
                    syncPreview();
                    scheduleCheck();
                });

                if (regenBtn) {
                    regenBtn.addEventListener('click', () => {
                        toggle.checked = true;
                        setManualMode(false);
                        applyAutoSlug();
                        scheduleCheck();
                    });
                }
            }

            if (form) {
                form.addEventListener('submit', (e) => {
                    if (form.dataset.slugOk === '0') {
                        e.preventDefault();
                        alert('Slug geçersiz/çakışıyor. Lütfen düzelt.');
                    }
                });
            }
        })();
    </script>
@endpush
