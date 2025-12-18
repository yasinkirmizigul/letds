@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6">

        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Blog Düzenle</h1>
                <div class="text-sm text-muted-foreground">ID: {{ $blogPost->id }} • Slug: {{ $blogPost->slug }}</div>
            </div>

            <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light">Geri</a>
        </div>

        @php
            $current = $blogPost->featured_image_path
                ? asset('storage/'.$blogPost->featured_image_path)
                : null;
        @endphp

        <div class="kt-card">

            {{-- UPDATE FORM (buttons are outside and linked via form="...") --}}
            <form id="blog-update-form"
                  class="kt-card-content p-8 flex flex-col gap-6"
                  method="POST"
                  action="{{ route('admin.blog.update', ['blogPost' => $blogPost->id]) }}"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- Left --}}
                    <div class="lg:col-span-2 flex flex-col gap-6">

                        {{-- Title + Slug auto toggle --}}
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono mb-0">Başlık</label>

                            <div class="flex items-center justify-between gap-3">
                                <input
                                    id="title"
                                    name="title"
                                    class="kt-input flex-1 @error('title') kt-input-invalid @enderror"
                                    value="{{ old('title', $blogPost->title) }}"
                                    required
                                >

                                <label class="inline-flex items-center gap-2 select-none">
                                    <span class="text-sm text-muted-foreground text-nowrap">Slug otomatik</span>
                                    <input
                                        id="slug_auto_toggle"
                                        type="checkbox"
                                        class="kt-switch kt-switch-mono"
                                        checked
                                    >
                                </label>
                            </div>

                            @error('title')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slug + regen --}}
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono mb-0">Slug</label>

                            <div class="flex items-center justify-between gap-3">
                                <input
                                    id="slug"
                                    name="slug"
                                    class="kt-input flex-1 @error('slug') kt-input-invalid @enderror"
                                    value="{{ old('slug', $blogPost->slug) }}"
                                    required
                                >

                                <div class="flex items-center gap-2">
                                    <span id="slug_mode_badge" class="kt-badge kt-badge-light hidden">Manuel</span>

                                    <button type="button"
                                            id="slug_regen_btn"
                                            class="kt-btn kt-btn-light kt-btn-sm">
                                        Başlıktan yeniden üret
                                    </button>
                                </div>
                            </div>

                            @error('slug')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror

                            <div class="text-sm text-muted-foreground">
                                URL Önizleme:
                                <span class="font-medium">
                                    {{ url('/blog') }}/
                                    <span id="url_slug_preview">{{ old('slug', $blogPost->slug) }}</span>
                                </span>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">İçerik</label>
                            <textarea
                                id="content_editor"
                                name="content"
                                class="kt-input min-h-[320px] @error('content') kt-input-invalid @enderror"
                            >{{ old('content', $blogPost->content ?? '') }}</textarea>
                            @error('content')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    {{-- Right --}}
                    <div class="lg:col-span-1 flex flex-col gap-6">

                        {{-- Categories (MISSING -> added) --}}
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Kategoriler</label>

                            <select name="category_ids[]" multiple
                                    class="kt-select @error('category_ids') kt-input-invalid @enderror"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Kategoriler...">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        @selected(collect(old('category_ids', $selectedCategoryIds ?? []))->contains($cat->id))>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('category_ids')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror

                            <div class="text-xs text-muted-foreground">
                                Çoklu seçebilirsin. Ürün/galeri de aynı kategori yapısını kullanacak.
                            </div>
                        </div>

                        {{-- SEO: meta keywords (MISSING -> added) --}}
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Anahtar Kelimeler</label>
                            <input class="kt-input @error('meta_keywords') kt-input-invalid @enderror"
                                   name="meta_keywords"
                                   value="{{ old('meta_keywords', $blogPost->meta_keywords ?? '') }}"
                                   placeholder="örn: veri analizi, istatistik"/>
                            @error('meta_keywords')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- SEO: meta description (MISSING -> added) --}}
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Açıklama</label>
                            <textarea
                                class="kt-input min-h-[90px] @error('meta_description') kt-input-invalid @enderror"
                                name="meta_description"
                                maxlength="255"
                                placeholder="Google snippet için kısa açıklama...">{{ old('meta_description', $blogPost->meta_description ?? '') }}</textarea>
                            @error('meta_description')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Featured image --}}
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Öne Çıkan Görsel</label>

                            <input id="featured_image"
                                   type="file"
                                   name="featured_image"
                                   accept="image/*"
                                   class="kt-input @error('featured_image') kt-input-invalid @enderror">

                            @error('featured_image')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror

                            <div class="mt-2">
                                <div class="text-sm text-muted-foreground mb-2">Mevcut / Önizleme</div>

                                <div id="featured_placeholder"
                                     class="rounded-xl border bg-muted {{ $current ? 'hidden' : '' }}"
                                     style="width:100%; height:220px;"></div>

                                <img id="featured_preview"
                                     src="{{ $current ?? '' }}"
                                     alt=""
                                     class="{{ $current ? '' : 'hidden' }} rounded-xl border"
                                     style="width:100%; height:220px; object-fit:cover;">
                            </div>

                            @if($current)
                                <div class="text-sm text-muted-foreground">
                                    Yeni görsel yüklersen eskisi otomatik silinir.
                                </div>
                            @endif
                        </div>

                        {{-- Publish --}}
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="font-medium">Yayınla</span>
                                <span class="text-sm text-muted-foreground">Açık olursa yayında</span>
                            </div>

                            <input type="checkbox"
                                   name="is_published"
                                   value="1"
                                   class="kt-switch kt-switch-mono"
                                @checked(old('is_published', $blogPost->is_published)) />
                        </div>

                        {{-- Buttons (same row: update + delete + cancel; no nested forms) --}}
                        <div class="flex gap-2 justify-center">
                            <button type="submit"
                                    form="blog-update-form"
                                    class="kt-btn kt-btn-primary">
                                Güncelle
                            </button>

                            @if(auth()->user()->hasPermission('blog.delete'))
                                <button type="button"
                                        class="kt-btn kt-btn-destructive"
                                        data-kt-modal-target="#deleteBlogModal">
                                    Sil
                                </button>
                            @endif

                            <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light">İptal</a>
                        </div>

                    </div>
                </div>
            </form>
        </div>

        {{-- DELETE FORM (separate) --}}
        @if(auth()->user()->hasPermission('blog.delete'))
            <form id="blog-delete-form"
                  method="POST"
                  action="{{ route('admin.blog.destroy', ['blogPost' => $blogPost->id]) }}">
                @csrf
                @method('DELETE')
            </form>

            {{-- DELETE MODAL --}}
            <div id="deleteBlogModal"
                 class="kt-modal hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="kt-card w-full max-w-md">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Blog Yazısını Sil</h3>
                    </div>

                    <div class="kt-card-content">
                        <p class="text-sm text-muted-foreground">
                            Bu blog yazısını silmek istediğine emin misin?
                            <br>
                            <strong>Bu işlem geri alınamaz.</strong>
                        </p>
                    </div>

                    <div class="kt-card-footer flex justify-end gap-2">
                        <button type="button"
                                class="kt-btn kt-btn-light"
                                data-kt-modal-close>
                            Vazgeç
                        </button>

                        <button type="submit"
                                form="blog-delete-form"
                                class="kt-btn kt-btn-destructive">
                            Evet, Sil
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection

@push('vendor_js')
    <script src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"></script>
@endpush

@push('page_js')
    <script>
        (function () {
            const SELECTOR = '#content_editor';
            const UPLOAD_URL = "{{ route('admin.tinymce.upload') }}";
            const CSRF = "{{ csrf_token() }}";

            function getTheme() {
                const root = document.documentElement;
                const body = document.body;
                const isDark = root.classList.contains('dark') || body.classList.contains('dark');
                return isDark ? 'dark' : 'light';
            }

            function initTiny(theme) {
                if (!window.tinymce) return;

                try { window.tinymce.remove(SELECTOR); } catch (e) {}

                window.tinymce.init({
                    selector: SELECTOR,
                    height: 420,

                    license_key: 'gpl',
                    base_url: "{{ asset('assets/vendors/tinymce') }}",
                    suffix: '.min',

                    language: 'tr',
                    language_url: "{{ asset('assets/vendors/tinymce/langs/tr.js') }}",

                    skin: theme === 'dark' ? 'oxide-dark' : 'oxide',
                    content_css: theme === 'dark' ? 'dark' : 'default',

                    plugins: 'lists link image code table',
                    toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image table | code',
                    menubar: false,

                    branding: false,
                    promotion: false,

                    automatic_uploads: true,
                    paste_data_images: true,

                    images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', UPLOAD_URL);
                        xhr.withCredentials = true;
                        xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);

                        xhr.upload.onprogress = (e) => {
                            if (e.lengthComputable) progress((e.loaded / e.total) * 100);
                        };

                        xhr.onload = () => {
                            if (xhr.status < 200 || xhr.status >= 300) return reject('Upload failed: ' + xhr.status);

                            let json;
                            try { json = JSON.parse(xhr.responseText); }
                            catch (e) { return reject('Invalid JSON'); }

                            if (!json || typeof json.location !== 'string') return reject('No location returned');
                            resolve(json.location);
                        };

                        xhr.onerror = () => reject('Network error');

                        const formData = new FormData();
                        formData.append('file', blobInfo.blob(), blobInfo.filename());
                        xhr.send(formData);
                    }),
                });
            }

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

            // Modal open/close (minimal)
            function openModal(sel) {
                const modal = document.querySelector(sel);
                if (modal) modal.classList.remove('hidden');
            }
            function closeModal(modal) {
                if (modal) modal.classList.add('hidden');
            }

            document.addEventListener('DOMContentLoaded', () => {
                // ---------- Featured image preview ----------
                const imgInput = document.getElementById('featured_image');
                const img = document.getElementById('featured_preview');
                const ph = document.getElementById('featured_placeholder');

                if (imgInput && img && ph) {
                    imgInput.addEventListener('change', () => {
                        const file = imgInput.files && imgInput.files[0] ? imgInput.files[0] : null;

                        if (!file) {
                            img.src = '';
                            img.classList.add('hidden');
                            ph.classList.remove('hidden');
                            return;
                        }

                        const url = URL.createObjectURL(file);
                        img.src = url;
                        img.classList.remove('hidden');
                        ph.classList.add('hidden');
                    });
                }

                // ---------- Slug (same behavior as create) ----------
                const titleInput = document.getElementById('title') || document.querySelector('input[name="title"]');
                const slugInput = document.getElementById('slug');
                const slugAutoToggle = document.getElementById('slug_auto_toggle');
                const slugRegenBtn = document.getElementById('slug_regen_btn');
                const slugModeBadge = document.getElementById('slug_mode_badge');
                const urlSlugPreview = document.getElementById('url_slug_preview');

                let slugLocked = slugInput && slugInput.value.trim().length > 0;

                function syncPreview() {
                    if (urlSlugPreview) urlSlugPreview.textContent = (slugInput?.value || '').trim();
                }

                function applyAutoSlug() {
                    if (!titleInput || !slugInput) return;
                    slugInput.value = slugifyTR(titleInput.value);
                    syncPreview();
                }

                function setManualMode(isManual) {
                    if (slugModeBadge) slugModeBadge.classList.toggle('hidden', !isManual);

                    if (slugInput) {
                        slugInput.style.boxShadow = isManual ? '0 0 0 2px rgba(245, 158, 11, .35)' : '';
                    }
                }

                if (titleInput && slugInput && slugAutoToggle) {
                    syncPreview();
                    // başlangıç: otomatik açık ama slug mevcut olduğu için "kilit" davranışı
                    // Eğer kullanıcı manuel dokunursa kilitle, toggle kapat.
                    setManualMode(false);

                    slugInput.addEventListener('input', () => {
                        const v = slugInput.value.trim();
                        slugLocked = v.length > 0;
                        // manuel yazmaya başladıysa auto kapat
                        if (slugLocked && slugAutoToggle.checked) {
                            slugAutoToggle.checked = false;
                            setManualMode(true);
                        }
                        syncPreview();
                    });

                    titleInput.addEventListener('input', () => {
                        if (!slugAutoToggle.checked) return;
                        if (slugLocked) return; // manuel doluysa override etme
                        applyAutoSlug();
                    });

                    slugAutoToggle.addEventListener('change', () => {
                        const isAuto = slugAutoToggle.checked;
                        setManualMode(!isAuto);
                        if (isAuto) {
                            slugLocked = false;
                            applyAutoSlug();
                        }
                    });

                    if (slugRegenBtn) {
                        slugRegenBtn.addEventListener('click', () => {
                            slugAutoToggle.checked = true;
                            setManualMode(false);
                            slugLocked = false;
                            applyAutoSlug();
                        });
                    }
                } else if (slugInput) {
                    syncPreview();
                    slugInput.addEventListener('input', syncPreview);
                }

                // ---------- TinyMCE ----------
                initTiny(getTheme());

                // ---------- Modal ----------
                document.querySelectorAll('[data-kt-modal-target]').forEach(btn => {
                    btn.addEventListener('click', () => openModal(btn.getAttribute('data-kt-modal-target')));
                });

                document.querySelectorAll('[data-kt-modal-close]').forEach(btn => {
                    btn.addEventListener('click', () => closeModal(btn.closest('.kt-modal')));
                });

                document.querySelectorAll('.kt-modal').forEach(modal => {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) closeModal(modal);
                    });
                });

                // ---------- Prevent double submit ----------
                const updateForm = document.getElementById('blog-update-form');
                if (updateForm) {
                    updateForm.addEventListener('submit', () => {
                        document.querySelectorAll('button[form="blog-update-form"][type="submit"]').forEach(b => {
                            b.disabled = true;
                            b.classList.add('opacity-60', 'pointer-events-none');
                        });
                    });
                }

                const deleteForm = document.getElementById('blog-delete-form');
                if (deleteForm) {
                    deleteForm.addEventListener('submit', () => {
                        document.querySelectorAll('button[form="blog-delete-form"][type="submit"]').forEach(b => {
                            b.disabled = true;
                            b.classList.add('opacity-60', 'pointer-events-none');
                        });
                    });
                }
            });

            // Theme observer
            let currentTheme = getTheme();
            const observer = new MutationObserver(() => {
                const next = getTheme();
                if (next === currentTheme) return;
                currentTheme = next;
                initTiny(currentTheme);
            });

            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>
@endpush
