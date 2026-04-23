@php
    $project = $project ?? null;
    $isEdit = filled($project?->id);
    $currentStatus = old('status', $project->status ?? \App\Models\Admin\Project\Project::STATUS_APPOINTMENT_PENDING);
    $currentFeatured = (bool) old('is_featured', (bool) ($project->is_featured ?? false));
    $currentAppointmentId = old('appointment_id', $project->appointment_id ?? '');
    $selectedCategoryIds = old('category_ids', $selectedCategoryIds ?? []);
    $featuredMediaId = old('featured_media_id', $featuredMediaId ?? null);
    $currentFeaturedUrl = $project?->featuredMediaUrl() ?? $project?->featured_image_url;
    $statusOptions = $statusOptions ?? \App\Models\Admin\Project\Project::statusOptionsSorted();
    $publicStatuses = $publicStatuses ?? \App\Models\Admin\Project\Project::PUBLIC_STATUSES;
    $currentStatusMeta = $statusOptions[$currentStatus] ?? ($statusOptions[\App\Models\Admin\Project\Project::STATUS_APPOINTMENT_PENDING] ?? null);
    $initialPublicVisible = in_array($currentStatus, $publicStatuses, true);
    $storedTranslations = old('translations');

    if (!is_array($storedTranslations)) {
        $storedTranslations = collect($project?->translations ?? [])
            ->mapWithKeys(fn ($translation) => [
                $translation->locale => [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'content' => $translation->content,
                    'meta_title' => $translation->meta_title,
                    'meta_description' => $translation->meta_description,
                    'meta_keywords' => $translation->meta_keywords,
                ],
            ])
            ->toArray();
    }
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.55fr)_400px] gap-6">
    <div class="grid gap-6">
        @include('admin.components.localized-content-tabs', [
            'moduleKey' => 'project',
            'title' => 'Proje İçerik Dilleri',
            'description' => 'Varsayılan dil ve ek diller için proje içeriğini, slug bilgisini ve SEO alanlarını sekmelerden yönetin.',
            'urlBase' => url('/projects'),
            'defaultValues' => [
                'title' => old('title', $project->title ?? ''),
                'slug' => old('slug', $project->slug ?? ''),
                'content' => old('content', $project->content ?? ''),
                'meta_title' => old('meta_title', $project->meta_title ?? ''),
                'meta_description' => old('meta_description', $project->meta_description ?? ''),
                'meta_keywords' => old('meta_keywords', $project->meta_keywords ?? ''),
            ],
            'storedTranslations' => $storedTranslations,
            'fields' => [
                ['name' => 'title', 'id' => 'title', 'label' => 'Başlık', 'placeholder' => 'Proje başlığını yazın', 'slug_source' => true],
                ['name' => 'slug', 'id' => 'slug', 'type' => 'slug', 'label' => 'Slug ve URL'],
                ['name' => 'content', 'id' => 'content_editor', 'type' => 'editor', 'rows' => 10, 'label' => 'Proje Detayı'],
                ['name' => 'meta_title', 'label' => 'Meta Başlık'],
                ['name' => 'meta_description', 'type' => 'textarea', 'rows' => 3, 'label' => 'Meta Açıklama'],
                ['name' => 'meta_keywords', 'label' => 'Meta Anahtar Kelimeler'],
            ],
        ])

        @if($isEdit)
            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Galeriler</h3>
                        <div class="text-sm text-muted-foreground">Proje galerilerini ana ve sidebar alanlarına göre yönetin.</div>
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
                    <div class="text-sm text-muted-foreground">Proje akış durumunu, public görünürlüğü ve anasayfa vitrini yönetin.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-4">
                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="grid gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium text-foreground">Workflow Durumu</div>
                            <span id="project_status_badge" data-status-badge class="{{ $currentStatusMeta['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light' }}">
                                {{ $currentStatusMeta['label'] ?? $currentStatus }}
                            </span>
                        </div>

                        <select id="project_status" name="status" class="kt-select w-full @error('status') kt-input-invalid @enderror" data-kt-select="true" data-kt-select-placeholder="Durum">
                            @foreach($statusOptions as $key => $option)
                                <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $option['label'] }}</option>
                            @endforeach
                        </select>

                        <span class="kt-badge kt-badge-sm {{ $initialPublicVisible ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}" data-project-public-badge>
                            {{ $initialPublicVisible ? 'Sitede görünebilir' : 'Sitede gizli' }}
                        </span>
                        <div class="text-xs text-muted-foreground" data-project-visibility-hint>
                            {{ $initialPublicVisible ? 'Bu statü, proje detay sayfasının site tarafında açılmasına izin verir.' : 'Bu statü, projeyi admin içinde tutar; site tarafında yayına çıkmaz.' }}
                        </div>
                        @error('status')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium text-foreground">Anasayfa Vitrini</div>
                            <div class="text-sm text-muted-foreground">En fazla 5 proje öne çıkarılabilir.</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_featured" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input type="checkbox" class="kt-switch" id="project_is_featured" name="is_featured" value="1" @checked($currentFeatured)>
                            </label>
                            <span id="project_featured_badge" class="kt-badge kt-badge-sm {{ $currentFeatured ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}">
                                {{ $currentFeatured ? 'Anasayfada' : 'Kapalı' }}
                            </span>
                        </div>
                    </div>
                    @error('is_featured')<div class="mt-2 text-xs text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Kategoriler</h3>
                    <div class="text-sm text-muted-foreground">Projeyi doğru kategorilerle ilişkilendirin.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-3">
                <select name="category_ids[]" multiple class="hidden" data-kt-select="true" data-kt-select-placeholder="Kategoriler" data-kt-select-multiple="true" data-kt-select-tags="false" data-kt-select-config='{"showSelectedCount":true,"enableSelectAll":true,"selectAllText":"Tümünü Seç","clearAllText":"Temizle"}'>
                    @foreach($categoryOptions ?? [] as $option)
                        <option value="{{ $option['id'] }}" @selected(in_array($option['id'], $selectedCategoryIds))>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('category_ids')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                @error('category_ids.*')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>
        </div>

        @include('admin.components.featured-image-manager', [
            'title' => 'Öne Çıkan Görsel',
            'hint' => 'Dosya yükleyebilir veya medya kütüphanesinden seçim yapabilirsiniz.',
            'fileName' => 'featured_image',
            'mediaIdName' => 'featured_media_id',
            'clearFlagName' => 'clear_featured_image',
            'currentMediaId' => $featuredMediaId,
            'currentUrl' => $currentFeaturedUrl,
        ])

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Ek Bilgiler</h3>
                    <div class="text-sm text-muted-foreground">İhtiyaca göre randevu kaydı ile eşleştirme yapın.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-3">
                <div class="grid gap-2">
                    <label class="kt-form-label" for="appointment_id">Randevu ID</label>
                    <input id="appointment_id" name="appointment_id" class="kt-input @error('appointment_id') kt-input-invalid @enderror" value="{{ $currentAppointmentId }}" placeholder="Opsiyonel">
                    @error('appointment_id')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>

                @if($isEdit)
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl app-surface-card px-4 py-3">Kayıt No: #{{ $project->id }}</div>
                        <div class="rounded-2xl app-surface-card px-4 py-3">Öne çıkma: {{ $project->featured_at?->format('d.m.Y H:i') ?: 'Yok' }}</div>
                        <div class="rounded-2xl app-surface-card px-4 py-3">Oluşturulma: {{ $project->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                        <div class="rounded-2xl app-surface-card px-4 py-3">Son güncelleme: {{ $project->updated_at?->format('d.m.Y H:i') ?: '-' }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
