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

                {{-- UPDATE FORM (no nested forms, buttons are outside and linked via form="...") --}}
                <form id="category-update-form" method="POST" action="{{ route('admin.categories.update', ['category' => $category->id]) }}">
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
                                        @error('name')
                                        <div class="text-xs text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="flex items-center justify-between gap-3">
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
                                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-end">
                                    <div class="lg:col-span-2 flex flex-col gap-2">
                                        <label class="kt-form-label font-normal text-mono">Slug</label>
                                        <div class="flex items-center justify-between gap-3">
                                            <input
                                                id="cat_slug"
                                                class="kt-input @error('slug') kt-input-invalid @enderror"
                                                name="slug"
                                                value="{{ old('slug', $category->slug) }}"
                                                required
                                            />

                                            <button type="button" id="slug_regen" class="kt-btn kt-btn-light">
                                                Yeniden üret
                                            </button>
                                        </div>

                                        @error('slug')
                                        <div class="text-xs text-danger">{{ $message }}</div>
                                        @enderror

                                        <div class="text-sm text-muted-foreground">
                                            URL önizleme:
                                            <span class="font-medium">/kategori/</span><span id="slug_preview" class="font-medium"></span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            {{-- RIGHT --}}
                            <div class="lg:col-span-1 flex flex-col gap-6">
                                {{-- Parent --}}
                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label font-normal text-mono">Üst Kategori</label>

                                    {{-- parent tek seçim olmalı: multiple/tags kaldırıldı --}}
                                    <select id="parent_id" name="parent_id"
                                            class="kt-select @error('parent_id') kt-input-invalid @enderror"
                                            data-kt-select="true"
                                            data-kt-select-placeholder="Üst Kategoriler...">
                                        <option value="">Üst kategori yok</option>
                                        @foreach(($parentOptions ?? []) as $opt)
                                            <option value="{{ $opt['id'] }}" @selected((string)old('parent_id', $category->parent_id) === (string)$opt['id'])>
                                                {{ $opt['label'] }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('parent_id')
                                    <div class="text-sm text-danger mt-1">{{ $message }}</div>
                                    @enderror

                                    <div class="text-sm text-muted-foreground">
                                        Not: Kendi alt kategorilerini üst kategori olarak seçemezsin.
                                    </div>
                                </div>

                                {{-- BUTTONS (same line) --}}
                                <div class="flex gap-2 justify-center">
                                    {{-- UPDATE --}}
                                    <button type="submit"
                                            form="category-update-form"
                                            class="kt-btn kt-btn-primary">
                                        Güncelle
                                    </button>

                                    {{-- DELETE (modal open) --}}
                                    @if(auth()->user()->hasPermission('category.delete'))
                                        <button type="button"
                                                class="kt-btn kt-btn-destructive"
                                                data-kt-modal-target="#deleteCategoryModal">
                                            Sil
                                        </button>
                                    @endif

                                    <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-mono">İptal</a>
                                </div>

                            </div>

                        </div>
                    </div>
                </form>

                {{-- DELETE FORM (separate; used by modal confirm) --}}
                @if(auth()->user()->hasPermission('category.delete'))
                    <form id="category-delete-form"
                          method="POST"
                          action="{{ route('admin.categories.destroy', ['category' => $category->id]) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif

            </div>

            {{-- DELETE MODAL --}}
            @if(auth()->user()->hasPermission('category.delete'))
                <div id="deleteCategoryModal"
                     class="kt-modal hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div class="kt-card max-w-md">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Kategori Sil</h3>
                        </div>

                        <div class="kt-card-content">
                            <p class="text-sm text-muted-foreground">
                                Bu kategoriyi silmek istediğine emin misin?
                                <br>
                                <strong>Alt kategoriler varsa silinemez.</strong>
                            </p>
                        </div>

                        <div class="kt-card-footer flex justify-end gap-2">
                            <button type="button"
                                    class="kt-btn kt-btn-mono"
                                    data-kt-modal-close>
                                Vazgeç
                            </button>

                            <button type="submit"
                                    form="category-delete-form"
                                    class="kt-btn kt-btn-destructive">
                                Evet, Sil
                            </button>
                        </div>
                    </div>
                </div>
            @endif

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

            // Modal open/close (minimal)
            function openModal(sel) {
                const modal = document.querySelector(sel);
                if (modal) modal.classList.remove('hidden');
            }
            function closeModal(modal) {
                if (modal) modal.classList.add('hidden');
            }

            document.addEventListener('DOMContentLoaded', () => {
                syncPreview();

                if (nameEl && autoEl) {
                    nameEl.addEventListener('input', () => {
                        if (!autoEl.checked) return;
                        setSlugFromName();
                    });
                }

                if (slugEl) slugEl.addEventListener('input', syncPreview);

                if (regenEl) {
                    regenEl.addEventListener('click', () => {
                        setSlugFromName();
                    });
                }

                // modal open
                document.querySelectorAll('[data-kt-modal-target]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        openModal(btn.getAttribute('data-kt-modal-target'));
                    });
                });

                // modal close
                document.querySelectorAll('[data-kt-modal-close]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        closeModal(btn.closest('.kt-modal'));
                    });
                });

                // close on backdrop click
                document.querySelectorAll('.kt-modal').forEach(modal => {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) closeModal(modal);
                    });
                });

                // prevent double submit on update
                const updateForm = document.getElementById('category-update-form');
                if (updateForm) {
                    updateForm.addEventListener('submit', () => {
                        document.querySelectorAll('button[form="category-update-form"][type="submit"]').forEach(b => {
                            b.disabled = true;
                            b.classList.add('opacity-60', 'pointer-events-none');
                        });
                    });
                }

                // prevent double submit on delete
                const deleteForm = document.getElementById('category-delete-form');
                if (deleteForm) {
                    deleteForm.addEventListener('submit', () => {
                        document.querySelectorAll('button[form="category-delete-form"][type="submit"]').forEach(b => {
                            b.disabled = true;
                            b.classList.add('opacity-60', 'pointer-events-none');
                        });
                    });
                }
            });
        })();
    </script>
@endpush
