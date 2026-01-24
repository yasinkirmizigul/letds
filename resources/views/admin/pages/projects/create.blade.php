@extends('admin.layouts.main.app')

@section('content')
    @php
        $currentStatus = old('status', \App\Models\Admin\Project\Project::STATUS_APPOINTMENT_PENDING);
        $currentFeatured = (bool) old('is_featured', false);
        $st = $statusOptions[$currentStatus] ?? $statusOptions[\App\Models\Admin\Project\Project::STATUS_APPOINTMENT_PENDING];
    @endphp

    <div class="kt-container-fixed"
         data-page="projects.create"
         data-upload-url="{{ Route::has('admin.tinymce.upload') ? route('admin.tinymce.upload') : url('/admin/tinymce/upload') }}"
         data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
         data-tinymce-base="{{ url('/assets/vendors/tinymce') }}"
         data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
         data-status-options='@json($statusOptions)'>

        @includeIf('admin.partials._flash')

        <form method="POST" action="{{ route('admin.projects.store') }}" class="grid gap-5 lg:gap-7.5">
            @csrf

            @include('admin.pages.projects.partials._form', [
                'project' => null,
                'categories' => $categories ?? collect(),
                'selectedCategoryIds' => $selectedCategoryIds ?? [],
                'featuredMediaId' => null,
            ])

            {{-- ✅ Durum + Anasayfa --}}
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
                                @foreach($statusOptions as $key => $opt)
                                    <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <span id="status_badge_preview"
                                  class="{{ $st['badge'] }} whitespace-nowrap"
                                  data-status-badge>
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
                            <input type="checkbox"
                                   id="is_featured"
                                   class="kt-switch"
                                   name="is_featured"
                                   value="1"
                                   data-featured-toggle
                                {{ $currentFeatured ? 'checked' : '' }}>

                            <span class="text-sm text-muted-foreground js-featured-label">
                            {{ $currentFeatured ? 'Anasayfada' : 'Kapalı' }}
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

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
                <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light">İptal</a>
            </div>
        </form>
    </div>

    @include('admin.pages.media.partials._upload-modal')
@endsection
