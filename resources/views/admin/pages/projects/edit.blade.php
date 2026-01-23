@extends('admin.layouts.main.app')

@section('content')
    @php
        $projectStatusOptions = [
            'appointment_pending'   => ['label' => 'Randevu Bekliyor',    'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning'],
            'appointment_scheduled' => ['label' => 'Randevu Planlandı',   'badge' => 'kt-badge kt-badge-sm kt-badge-light-primary'],
            'appointment_done'      => ['label' => 'Randevu Tamamlandı',  'badge' => 'kt-badge kt-badge-sm kt-badge-light-success'],
            'dev_pending'           => ['label' => 'Geliştirme Bekliyor', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning'],
            'dev_in_progress'       => ['label' => 'Geliştirme Devam',    'badge' => 'kt-badge kt-badge-sm kt-badge-primary'],
            'delivered'             => ['label' => 'Teslim Edildi',       'badge' => 'kt-badge kt-badge-sm kt-badge-light-info'],
            'approved'              => ['label' => 'Onaylandı',           'badge' => 'kt-badge kt-badge-sm kt-badge-light-success'],
            'closed'                => ['label' => 'Kapatıldı',           'badge' => 'kt-badge kt-badge-sm kt-badge-light'],
        ];
    @endphp

    <div class="kt-container-fixed"
         data-page="projects.edit"
         data-id="{{ $project->id }}"
         data-upload-url="{{ Route::has('admin.tinymce.upload') ? route('admin.tinymce.upload') : url('/admin/tinymce/upload') }}"
         data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
         data-tinymce-base="{{ url('/assets/vendors/tinymce') }}"
         data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
         data-status-options='@json($projectStatusOptions)'>

        @includeIf('admin.partials._flash')

        <form method="POST"
              action="{{ route('admin.projects.update', $project) }}"
              class="grid gap-5 lg:gap-7.5"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.pages.projects.partials._form', [
                'project' => $project,
                'categories' => $categories ?? collect(),
                'selectedCategoryIds' => $selectedCategoryIds ?? [],
                'featuredMediaId' => $featuredMediaId ?? null,
            ])

            {{-- Durum + Anasayfa --}}
            @php
                $currentStatus = old('status', $project?->status ?? 'appointment_pending');
                $currentFeatured = (bool) old('is_featured', $project?->is_featured ?? false);
                $st = $projectStatusOptions[$currentStatus] ?? $projectStatusOptions['appointment_pending'];
            @endphp

            <div class="kt-card">
                <div class="kt-card-header py-4">
                    <h3 class="kt-card-title">Durum &amp; Anasayfa</h3>
                </div>

                <div class="kt-card-body grid gap-5 p-5">
                    <div class="grid gap-2">
                        <label for="status" class="kt-label">Durum</label>

                        <div class="flex items-center gap-3">
                            <select id="status"
                                    name="status"
                                    class="kt-select w-full"
                                    data-status-select>
                                @foreach($projectStatusOptions as $key => $opt)
                                    <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <span id="status_badge_preview" class="{{ $st['badge'] }} whitespace-nowrap" data-status-badge>
                                {{ $st['label'] }}
                            </span>
                        </div>

                        @error('status')
                        <div class="kt-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-label">Anasayfada göster</label>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_featured" value="0" />
                            <input
                                type="checkbox"
                                id="is_featured"
                                class="kt-switch"
                                name="is_featured"
                                value="1"
                                data-featured-toggle
                                {{ $currentFeatured ? 'checked' : '' }}
                            >

                            <span class="text-sm text-muted-foreground js-featured-label">
                                {{ $currentFeatured ? 'Anasayfada' : 'Kapalı' }}
                            </span>

                            <span class="kt-badge kt-badge-sm kt-badge-light-success js-featured-badge transition-opacity duration-200 {{ $currentFeatured ? 'opacity-100' : 'opacity-0' }} {{ $currentFeatured ? '' : 'hidden' }}">
                                Anasayfada
                            </span>
                        </div>

                        <div class="text-xs text-muted-foreground">
                            En fazla 5 proje aynı anda anasayfada görünebilir.
                        </div>

                        @error('is_featured')
                        <div class="kt-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <button type="button" id="projectDeleteBtn" class="kt-btn kt-btn-danger">Sil</button>

                <div class="flex items-center gap-3">
                    <button type="submit" class="kt-btn kt-btn-primary">Güncelle</button>
                    <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light">Geri</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Media upload modal --}}
    @include('admin.pages.media.partials._upload-modal')
@endsection
