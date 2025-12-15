@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6">

        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Yeni Blog Yazısı</h1>
                <div class="text-sm text-muted-foreground">Başlık, içerik ve öne çıkan görsel</div>
            </div>

            <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light">
                Geri
            </a>
        </div>

        <div class="kt-card">
            <form class="kt-card-content p-8 flex flex-col gap-6"
                  method="POST"
                  action="{{ route('admin.blog.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- Left --}}
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Başlık</label>
                            <input
                                class="kt-input @error('title') kt-input-invalid @enderror"
                                name="title"
                                value="{{ old('title') }}"
                                placeholder="Başlık"
                                required
                            />
                            @error('title') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Slug</label>
                            <input
                                id="slug"
                                class="kt-input @error('slug') kt-input-invalid @enderror"
                                name="slug"
                                value="{{ old('slug') }}"
                                placeholder="otomatik oluşur (istersen değiştir)"
                            />
                            @error('slug') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">İçerik</label>
                            <textarea
                                class="kt-input min-h-[260px] @error('content') kt-input-invalid @enderror"
                                name="content"
                                placeholder="Yazı içeriği...">{{ old('content') }}</textarea>
                            @error('content') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Right --}}
                    <div class="lg:col-span-1 flex flex-col gap-6">

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Öne Çıkan Görsel</label>

                            <input id="featured_image"
                                   type="file"
                                   name="featured_image"
                                   accept="image/*"
                                   class="kt-input @error('featured_image') kt-input-invalid @enderror">

                            @error('featured_image') <div class="text-xs text-danger">{{ $message }}</div> @enderror

                            <div class="mt-2">
                                <div class="text-sm text-muted-foreground mb-2">Önizleme</div>

                                <div id="featured_placeholder"
                                     class="rounded-xl border bg-muted"
                                     style="width:100%; height:220px;"></div>

                                <img id="featured_preview"
                                     src=""
                                     alt=""
                                     class="hidden rounded-xl border"
                                     style="width:100%; height:220px; object-fit:cover;">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="font-medium">Yayınla</span>
                                <span class="text-sm text-muted-foreground">Açık olursa yayında</span>
                            </div>

                            <label class="kt-switch kt-switch-sm">
                                <input type="checkbox"
                                       name="is_published"
                                       value="1"
                                    @checked(old('is_published'))>
                                <span class="kt-switch-indicator"></span>
                            </label>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="kt-btn kt-btn-primary w-full">Kaydet</button>
                            <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light w-full">İptal</a>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('page_js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ---------- Featured image preview ----------
            const imgInput = document.getElementById('featured_image');
            const img = document.getElementById('featured_preview');
            const ph = document.getElementById('featured_placeholder');

            if (imgInput) {
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

            // ---------- Slug auto-generate ----------
            const titleInput = document.querySelector('input[name="title"]');
            const slugInput  = document.getElementById('slug');

            if (!titleInput || !slugInput) return;

            // create: kullanıcı slug'u elle değiştirirse artık otomatik güncellemeyelim
            let slugLocked = slugInput.value.trim().length > 0;

            slugInput.addEventListener('input', () => {
                slugLocked = slugInput.value.trim().length > 0;
            });

            function slugifyTR(str) {
                return String(str)
                    .trim()
                    .toLowerCase()
                    .replaceAll('ğ','g').replaceAll('ü','u').replaceAll('ş','s')
                    .replaceAll('ı','i').replaceAll('ö','o').replaceAll('ç','c')
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // aksan temizle
                    .replace(/[^a-z0-9\s-]/g, '') // izinli karakterler
                    .replace(/\s+/g, '-')        // boşluk -> -
                    .replace(/-+/g, '-')         // çoklu - -> tek
                    .replace(/^-|-$/g, '');      // baş/son - temizle
            }

            titleInput.addEventListener('input', () => {
                if (slugLocked) return;
                slugInput.value = slugifyTR(titleInput.value);
            });
        });
    </script>
@endpush

