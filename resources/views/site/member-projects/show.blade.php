@extends('site.layouts.main.app')

@section('content')
    <div class="site-page">
        @include('site.partials.member-nav')

        <header class="mt-7 rounded-[2rem] border border-border bg-background p-6 shadow-sm md:p-9" data-reveal>
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}" class="site-eyebrow">Projelerime dön</a>
                    <h1 class="mt-6 max-w-4xl font-display text-3xl font-semibold leading-tight text-foreground md:text-5xl">{{ $project->localizedValue('title') }}</h1>
                    <div class="mt-5 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                        <span class="{{ \App\Models\Admin\Project\Project::statusBadgeClass($project->status) }}">{{ \App\Models\Admin\Project\Project::statusLabel($project->status) }}</span>
                        <span>Proje #{{ $project->id }}</span>
                        <span>Son güncelleme {{ $project->updated_at->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
                @if($project->serviceReview)
                    <a href="{{ route('member.reviews.show', ['serviceReview' => $project->serviceReview, 'site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary shrink-0">
                        {{ $project->serviceReview->isPending() ? 'Projeyi değerlendir' : 'Değerlendirmemi gör' }}
                    </a>
                @endif
            </div>

            <div class="mt-8 site-workflow" aria-label="Proje ilerleme durumu">
                @foreach($workflowSteps as $step)
                    <div class="site-workflow-step {{ $step['is_complete'] ? 'is-complete' : '' }} {{ $step['is_current'] ? 'is-current' : '' }}">
                        <span>{{ $step['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </header>

        @if(session('success'))
            <div class="mt-6 rounded-2xl border border-success/25 bg-success/10 px-4 py-4 text-sm text-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="mt-6 rounded-2xl border border-danger/25 bg-danger/10 px-4 py-4 text-sm text-danger">
                <div class="font-semibold">Dosya işlemi tamamlanamadı.</div>
                <div class="mt-1">{{ $errors->first() }}</div>
            </div>
        @endif

        <div class="mt-8 grid gap-7 lg:grid-cols-[minmax(0,1.25fr)_minmax(20rem,.75fr)]">
            <section class="grid gap-7">
                <div class="rounded-3xl border border-border bg-background p-6 md:p-8">
                    <span class="site-eyebrow">Proje özeti</span>
                    <div class="site-prose mt-6">
                        {!! $project->localizedValue('content') ?: '<p>Proje kapsamı ekip tarafından güncellenecek.</p>' !!}
                    </div>
                </div>

                <div class="rounded-3xl border border-border bg-background p-6 md:p-8">
                    <div class="site-section-heading mb-6">
                        <div>
                            <span class="site-eyebrow">Dosya alanı</span>
                            <h2 class="mt-4 site-section-title">Paylaşılan dosyalar</h2>
                        </div>
                        <span class="text-sm text-muted-foreground">{{ $project->files->count() }} dosya</span>
                    </div>

                    <div class="grid gap-3">
                        @forelse($project->files as $file)
                            <div class="site-file-row">
                                <span class="site-file-icon">{{ \Illuminate\Support\Str::upper(pathinfo($file->original_name, PATHINFO_EXTENSION) ?: 'FILE') }}</span>
                                <div class="min-w-0">
                                    <a href="{{ route('member.projects.files.download', ['project' => $project, 'projectFile' => $file, 'site_locale' => $siteCurrentLocale]) }}" class="block truncate text-sm font-semibold text-foreground hover:text-primary">{{ $file->original_name }}</a>
                                    <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                                        <span>{{ $file->sizeLabel() }}</span>
                                        <span>{{ $file->created_at->format('d.m.Y H:i') }}</span>
                                        @if($file->member)<span>{{ $file->member->full_name }}</span>@endif
                                    </div>
                                    @if($file->note)<p class="mt-2 text-xs leading-5 text-muted-foreground">{{ $file->note }}</p>@endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('member.projects.files.download', ['project' => $project, 'projectFile' => $file, 'site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-sm kt-btn-light" aria-label="{{ $file->original_name }} dosyasını indir">İndir</a>
                                    @if((int) $file->member_id === (int) auth('member')->id() && $project->allowsMemberUploads())
                                        <form method="POST" action="{{ route('member.projects.files.destroy', ['project' => $project, 'projectFile' => $file, 'site_locale' => $siteCurrentLocale]) }}" data-project-file-delete>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-danger" aria-label="{{ $file->original_name }} dosyasını sil">Sil</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-border px-5 py-10 text-center text-sm text-muted-foreground">Bu projede henüz dosya paylaşılmadı.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <aside class="grid content-start gap-7">
                <div class="rounded-3xl border border-border bg-background p-6">
                    <span class="site-eyebrow">Süreç bilgileri</span>
                    <dl class="mt-6 grid gap-4 text-sm">
                        <div class="border-b border-border pb-4">
                            <dt class="text-xs uppercase tracking-[0.14em] text-muted-foreground">Durum</dt>
                            <dd class="mt-2 font-semibold text-foreground">{{ \App\Models\Admin\Project\Project::statusLabel($project->status) }}</dd>
                        </div>
                        @if($project->appointment?->provider)
                            <div class="border-b border-border pb-4">
                                <dt class="text-xs uppercase tracking-[0.14em] text-muted-foreground">Proje sorumlusu</dt>
                                <dd class="mt-2 font-semibold text-foreground">{{ $project->appointment->provider->name }}</dd>
                            </div>
                        @endif
                        @if($project->appointment?->start_at)
                            <div>
                                <dt class="text-xs uppercase tracking-[0.14em] text-muted-foreground">Randevu tarihi</dt>
                                <dd class="mt-2 font-semibold text-foreground">{{ $project->appointment->start_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if($project->allowsMemberUploads())
                    <form method="POST" action="{{ route('member.projects.files.store', ['project' => $project, 'site_locale' => $siteCurrentLocale]) }}" enctype="multipart/form-data" class="rounded-3xl border border-border bg-background p-6">
                        @csrf
                        <span class="site-eyebrow">Yeni paylaşım</span>
                        <label class="site-file-drop mt-6" for="project_files">
                            <span>
                                <span class="mx-auto grid size-12 place-items-center rounded-full bg-primary/10 text-2xl text-primary">+</span>
                                <strong class="mt-4 block text-sm text-foreground">Dosyaları seçin</strong>
                                <span class="mt-2 block text-xs leading-6 text-muted-foreground">En fazla 5 dosya, dosya başına 20 MB.<br>PDF, Office, ZIP ve görseller.</span>
                                <span class="mt-3 block text-xs font-semibold text-primary" data-file-selection>Dosya seçilmedi</span>
                            </span>
                        </label>
                        <input id="project_files" type="file" name="files[]" class="sr-only" multiple required data-project-files accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.zip,.jpg,.jpeg,.png,.webp,.txt">

                        <div class="mt-5 grid gap-2">
                            <label for="project_file_note" class="kt-form-label">Dosya notu <span class="font-normal text-muted-foreground">(isteğe bağlı)</span></label>
                            <textarea id="project_file_note" name="note" class="kt-input" rows="3" maxlength="500" placeholder="Ekibin bilmesi gereken kısa bir not..."></textarea>
                        </div>
                        <button type="submit" class="kt-btn kt-btn-primary mt-5 w-full">Dosyaları projeye ekle</button>
                    </form>
                @else
                    <div class="rounded-3xl border border-border bg-muted/40 p-6">
                        <span class="site-eyebrow">Dosya alanı kapalı</span>
                        <p class="mt-4 text-sm leading-7 text-muted-foreground">Bu proje aşamasında yeni dosya yüklenemiyor. Mevcut dosyalarınızı indirmeye devam edebilirsiniz.</p>
                    </div>
                @endif
            </aside>
        </div>
    </div>
@endsection

@push('site_js')
    @vite('resources/js/site/member-portal.js')
@endpush
