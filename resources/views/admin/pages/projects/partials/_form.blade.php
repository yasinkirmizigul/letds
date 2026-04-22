@php
    $project = $project ?? null;
    $isEdit = filled($project?->id);

    $currentTitle = old('title', $project->title ?? '');
    $currentSlug = old('slug', $project->slug ?? '');
    $currentContent = old('content', $project->content ?? '');
    $currentMetaTitle = old('meta_title', $project->meta_title ?? '');
    $currentMetaDescription = old('meta_description', $project->meta_description ?? '');
    $currentMetaKeywords = old('meta_keywords', $project->meta_keywords ?? '');
    $currentStatus = old('status', $project->status ?? \App\Models\Admin\Project\Project::STATUS_APPOINTMENT_PENDING);
    $currentFeatured = (bool) old('is_featured', (bool) ($project->is_featured ?? false));
    $currentAppointmentId = old('appointment_id', $project->appointment_id ?? '');
    $selectedCategoryIds = old('category_ids', $selectedCategoryIds ?? []);
    $featuredMediaId = old('featured_media_id', $featuredMediaId ?? null);
    $currentFeaturedUrl = $project?->featuredMediaUrl() ?? $project?->featured_image_url;
    $statusOptions = $statusOptions ?? \App\Models\Admin\Project\Project::statusOptionsSorted();
    $publicStatuses = $publicStatuses ?? \App\Models\Admin\Project\Project::PUBLIC_STATUSES;
    $currentStatusMeta = $statusOptions[$currentStatus] ?? ($statusOptions[\App\Models\Admin\Project\Project::STATUS_APPOINTMENT_PENDING] ?? null);
    $initialWordCount = $isEdit ? $project->contentWordCount() : 0;
    $initialReadTime = $isEdit ? $project->estimatedReadTimeMinutes() : 0;
    $initialSeoScore = $isEdit ? $project->seoCompletenessScore() : 0;
    $initialPublicVisible = in_array($currentStatus, $publicStatuses, true);
    $previewTitle = $currentMetaTitle ?: ($currentTitle ?: 'Meta baslik burada gorunecek');
    $previewDescription = $currentMetaDescription ?: ($project?->excerptPreview(155) ?: 'Meta aciklama burada gorunecek.');
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.55fr)_400px] gap-6">
    <div class="grid gap-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Proje Icerigi</h3>
                    <div class="text-sm text-muted-foreground">
                        Baslik, slug ve proje detay metnini tek akista yonetin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-6">
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono" for="title">Baslik</label>
                        <span class="text-xs text-muted-foreground" data-project-title-count>{{ mb_strlen($currentTitle) }}/255</span>
                    </div>
                    <input
                        id="title"
                        name="title"
                        class="kt-input @error('title') kt-input-invalid @enderror"
                        value="{{ $currentTitle }}"
                        placeholder="Proje basligini yazin"
                    >
                    @error('title')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-3 rounded-3xl border border-border bg-muted/10 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono mb-0" for="slug">Slug ve URL</label>
                        <span class="text-xs text-muted-foreground">URL stabilitesini korumak icin sadece gerektiginde degistirin.</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            id="slug"
                            name="slug"
                            class="kt-input flex-1 @error('slug') kt-input-invalid @enderror"
                            value="{{ $currentSlug }}"
                            placeholder="otomatik-olusturulur"
                        >

                        <button type="button" id="slug_regen" class="kt-btn kt-btn-light">Olustur</button>

                        <label class="kt-switch shrink-0" title="Otomatik slug">
                            <input
                                type="checkbox"
                                class="kt-switch"
                                id="slug_auto"
                                @checked($currentSlug === '')
                            >
                            <span class="kt-switch-slider"></span>
                        </label>
                    </div>

                    @error('slug')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror

                    <div class="rounded-2xl border border-border bg-white px-4 py-3 text-sm text-muted-foreground">
                        URL onizleme:
                        <span class="font-medium text-foreground">{{ url('/projects') }}/<span id="url_slug_preview">{{ $currentSlug }}</span></span>
                    </div>

                    <div id="slugCheckHint" class="text-xs text-muted-foreground">
                        Slug girildiginde uygunluk kontrolu yapilir.
                    </div>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono" for="content_editor">Proje Detayi</label>
                        <span class="text-xs text-muted-foreground">TinyMCE ile zengin icerik duzenleme</span>
                    </div>
                    <textarea
                        id="content_editor"
                        name="content"
                        class="kt-textarea @error('content') kt-input-invalid @enderror"
                    >{{ $currentContent }}</textarea>
                    @error('content')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">SEO ve Arama Onizlemesi</h3>
                    <div class="text-sm text-muted-foreground">
                        Meta alanlarini girerken arama sonucunda gorunumu canli izleyin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_320px]">
                <div class="grid gap-5">
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="kt-form-label font-normal text-mono">Meta Title</label>
                            <span class="text-xs text-muted-foreground" data-project-meta-title-count>{{ mb_strlen($currentMetaTitle) }}/60 onerisi</span>
                        </div>
                        <input
                            name="meta_title"
                            class="kt-input @error('meta_title') kt-input-invalid @enderror"
                            value="{{ $currentMetaTitle }}"
                            placeholder="Arama sonucunda gorunecek baslik"
                        >
                        @error('meta_title')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="kt-form-label font-normal text-mono">Meta Description</label>
                            <span class="text-xs text-muted-foreground" data-project-meta-description-count>{{ mb_strlen($currentMetaDescription) }}/160 onerisi</span>
                        </div>
                        <textarea
                            name="meta_description"
                            rows="4"
                            class="kt-textarea @error('meta_description') kt-input-invalid @enderror"
                            placeholder="Arama sonucunda gorunecek aciklama"
                        >{{ $currentMetaDescription }}</textarea>
                        @error('meta_description')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono">Meta Keywords</label>
                        <input
                            name="meta_keywords"
                            class="kt-input @error('meta_keywords') kt-input-invalid @enderror"
                            value="{{ $currentMetaKeywords }}"
                            placeholder="anahtar,kelimeler,seklinde"
                        >
                        @error('meta_keywords')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 self-start">
                    <div class="rounded-[28px] border border-border bg-white p-5 shadow-sm">
                        <div class="text-[11px] uppercase tracking-[0.24em] text-muted-foreground">Arama Onizlemesi</div>
                        <div class="mt-4 grid gap-2">
                            <div class="text-base font-semibold leading-6 text-primary" data-project-seo-preview-title>
                                {{ $previewTitle }}
                            </div>
                            <div class="text-sm text-success">
                                {{ url('/projects') }}/<span data-project-seo-preview-slug>{{ $currentSlug ?: 'ornek-proje' }}</span>
                            </div>
                            <div class="text-sm leading-6 text-muted-foreground" data-project-seo-preview-description>
                                {{ $previewDescription }}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-border bg-muted/10 p-4 text-sm text-muted-foreground">
                        Meta title icin 30-60, meta description icin 100-160 karakter araligi daha dengeli gorunur.
                    </div>
                </div>
            </div>
        </div>

        @if($isEdit)
            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Galeriler</h3>
                        <div class="text-sm text-muted-foreground">
                            Proje galerilerini ana ve sidebar alanlarina gore yonetebilirsiniz.
                        </div>
                    </div>
                </div>

                <div class="kt-card-content p-6">
                    @include('admin.pages.projects.partials._gallery', ['project' => $project])
                </div>
            </div>
        @endif
    </div>

    <div class="grid gap-6 self-start xl:sticky xl:top-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Workflow ve Vitrin</h3>
                    <div class="text-sm text-muted-foreground">
                        Proje akis durumunu, public gorunurlugu ve anasayfa vitrini yonetin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-4">
                <div class="rounded-3xl border border-border bg-muted/10 p-4">
                    <div class="grid gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium text-foreground">Workflow Durumu</div>
                            <span
                                id="project_status_badge"
                                data-status-badge
                                class="{{ $currentStatusMeta['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light' }}"
                            >
                                {{ $currentStatusMeta['label'] ?? $currentStatus }}
                            </span>
                        </div>

                        <select
                            id="project_status"
                            name="status"
                            class="kt-select w-full @error('status') kt-input-invalid @enderror"
                            data-kt-select="true"
                            data-kt-select-placeholder="Durum"
                        >
                            @foreach($statusOptions as $key => $option)
                                <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $option['label'] }}</option>
                            @endforeach
                        </select>

                        <div class="flex items-center gap-2">
                            <span
                                class="kt-badge kt-badge-sm {{ $initialPublicVisible ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}"
                                data-project-public-badge
                            >
                                {{ $initialPublicVisible ? 'Sitede gorunebilir' : 'Sitede gizli' }}
                            </span>
                        </div>

                        <div class="text-xs text-muted-foreground" data-project-visibility-hint>
                            {{ $initialPublicVisible ? 'Bu statu, proje detay sayfasinin site tarafinda acilmasina izin verir.' : 'Bu statu, projeyi admin icinde tutar; site tarafinda yayina cikmaz.' }}
                        </div>

                        @error('status')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="rounded-3xl border border-border bg-muted/10 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid gap-1">
                            <div class="font-medium text-foreground">Anasayfa Vitrini</div>
                            <div class="text-sm text-muted-foreground">En fazla 5 proje one cikarilabilir.</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_featured" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input
                                    type="checkbox"
                                    class="kt-switch"
                                    id="project_is_featured"
                                    name="is_featured"
                                    value="1"
                                    @checked($currentFeatured)
                                >
                            </label>
                            <span
                                id="project_featured_badge"
                                class="kt-badge kt-badge-sm {{ $currentFeatured ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}"
                            >
                                {{ $currentFeatured ? 'Anasayfada' : 'Kapali' }}
                            </span>
                        </div>
                    </div>

                    @error('is_featured')
                        <div class="mt-2 text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Icerik Icgoruleri</h3>
                    <div class="text-sm text-muted-foreground">
                        Kelime yogunlugu ve SEO tamamliligini canli takip edin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-3xl border border-border bg-white p-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Kelime</div>
                        <div class="mt-2 text-2xl font-semibold text-foreground" data-project-word-count>{{ $initialWordCount }} kelime</div>
                    </div>
                    <div class="rounded-3xl border border-border bg-white p-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Okuma</div>
                        <div class="mt-2 text-2xl font-semibold text-foreground" data-project-read-time>{{ $initialReadTime }} dk</div>
                    </div>
                </div>

                <div class="rounded-3xl border border-border bg-muted/10 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">SEO Tamamlilik</div>
                            <div class="mt-1 text-sm text-muted-foreground" data-project-seo-summary>
                                {{ $initialSeoScore >= 80 ? 'SEO hazirligi guclu gorunuyor.' : ($initialSeoScore >= 50 ? 'Temel alanlar iyi, birkac iyilestirme daha yapilabilir.' : 'Meta alanlari ve one cikan gorsel tarafini guclendirmek faydali olur.') }}
                            </div>
                        </div>
                        <div
                            class="text-3xl font-semibold {{ $initialSeoScore >= 80 ? 'text-success' : ($initialSeoScore >= 50 ? 'text-warning' : 'text-danger') }}"
                            data-project-seo-score
                        >
                            %{{ $initialSeoScore }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Kategoriler</h3>
                    <div class="text-sm text-muted-foreground">
                        Projeyi dogru kategorilerle etiketleyerek bulunurlugu artirin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-3">
                <select
                    name="category_ids[]"
                    multiple
                    class="hidden"
                    data-kt-select="true"
                    data-kt-select-placeholder="Kategoriler"
                    data-kt-select-multiple="true"
                    data-kt-select-tags="false"
                    data-kt-select-config='{"showSelectedCount":true,"enableSelectAll":true,"selectAllText":"Tumunu Sec","clearAllText":"Temizle"}'
                >
                    @foreach($categoryOptions ?? [] as $option)
                        <option value="{{ $option['id'] }}" @selected(in_array($option['id'], $selectedCategoryIds))>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>

                <div class="text-xs text-muted-foreground">
                    Birden fazla kategori secilebilir. Alt kategoriler hiyerarsi korunarak listelenir.
                </div>

                @error('category_ids')
                    <div class="text-xs text-danger">{{ $message }}</div>
                @enderror
                @error('category_ids.*')
                    <div class="text-xs text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @include('admin.components.featured-image-manager', [
            'title' => 'One Cikan Gorsel',
            'hint' => 'Dosya yukleyebilir veya medya kutuphanesinden secim yapabilirsiniz.',
            'fileName' => 'featured_image',
            'mediaIdName' => 'featured_media_id',
            'clearFlagName' => 'clear_featured_image',
            'currentMediaId' => $featuredMediaId,
            'currentUrl' => $currentFeaturedUrl,
        ])

        @error('featured_image')
            <div class="text-xs text-danger -mt-3">{{ $message }}</div>
        @enderror
        @error('featured_media_id')
            <div class="text-xs text-danger -mt-3">{{ $message }}</div>
        @enderror

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Ek Bilgiler</h3>
                    <div class="text-sm text-muted-foreground">
                        Ihtiyaca gore randevu kaydi ile eslestirme yapabilirsiniz.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-3">
                <div class="grid gap-2">
                    <label class="kt-form-label font-normal text-mono" for="appointment_id">Randevu ID</label>
                    <input
                        id="appointment_id"
                        name="appointment_id"
                        class="kt-input @error('appointment_id') kt-input-invalid @enderror"
                        value="{{ $currentAppointmentId }}"
                        placeholder="Opsiyonel"
                    >
                    @error('appointment_id')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                @if($isEdit)
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl border border-border bg-white px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Kayit No</div>
                            <div class="mt-1 font-medium text-foreground">#{{ $project->id }}</div>
                        </div>
                        <div class="rounded-2xl border border-border bg-white px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">One Cikma Tarihi</div>
                            <div class="mt-1 font-medium text-foreground">{{ $project->featured_at?->format('d.m.Y H:i') ?: 'Yok' }}</div>
                        </div>
                        <div class="rounded-2xl border border-border bg-white px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Olusturulma</div>
                            <div class="mt-1 font-medium text-foreground">{{ $project->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                        </div>
                        <div class="rounded-2xl border border-border bg-white px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Son Guncelleme</div>
                            <div class="mt-1 font-medium text-foreground">{{ $project->updated_at?->format('d.m.Y H:i') ?: '-' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
