@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%]">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground">Ek Analiz Talepleri</h1>
            <p class="mt-2 text-sm text-muted-foreground">Uzman ve üye arasındaki tüm aşamaları ve belgeleri çalışma bazında izleyin.</p>
        </div>

        <section class="kt-card mb-6 p-6">
            <h2 class="text-lg font-semibold text-foreground">{{ auth()->user()->isAdmin() ? 'Tüm analiz çalışmaları' : 'Atandığım çalışmalar' }}</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @forelse($projects as $project)
                    <a href="{{ route('admin.analysis-requests.projects.show', $project) }}" class="rounded-xl border border-border p-4 hover:border-primary/50">
                        <span class="font-semibold text-foreground">{{ $project->title }}</span>
                        <span class="mt-1 block text-xs text-muted-foreground">{{ $project->member?->full_name }} · {{ \App\Models\Admin\Project\Project::statusLabel($project->status) }}</span>
                        @if($project->unread_events_count > 0 && ! auth()->user()->isAdmin())<span class="mt-2 inline-block rounded-full bg-primary/10 px-2 py-1 text-xs text-primary">{{ $project->unread_events_count }} yeni bildirim</span>@endif
                    </a>
                @empty
                    <p class="text-sm text-muted-foreground">Henüz atanmış çalışma yok.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $projects->links() }}</div>
        </section>

        @forelse($analysisRequests as $analysisRequest)
            <article class="kt-card mb-4 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">{{ $analysisRequest->project?->title ?: 'Proje' }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ $analysisRequest->member?->full_name ?: 'Üye' }} · {{ $analysisRequest->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                    <span class="kt-badge kt-badge-light-primary">{{ \App\Models\Admin\Project\ProjectAnalysisRequest::statusLabel($analysisRequest->status) }}</span>
                </div>
                <p class="mt-5 whitespace-pre-wrap text-sm leading-7 text-foreground">{{ $analysisRequest->message }}</p>

                @if($analysisRequest->files->isNotEmpty())
                    <div class="mt-5 border-t border-border pt-4">
                        <h3 class="mb-3 text-sm font-semibold">Ek belgeler ({{ $analysisRequest->files->count() }})</h3>
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach($analysisRequest->files as $requestFile)
                                <a href="{{ route('admin.analysis-requests.files.download', [$analysisRequest, $requestFile]) }}" class="flex items-center justify-between gap-3 rounded-xl border border-border px-4 py-3 text-sm hover:border-primary/50">
                                    <span class="min-w-0 truncate">{{ $requestFile->original_name }}</span>
                                    <span class="shrink-0 text-xs text-muted-foreground">{{ $requestFile->sizeLabel() }} · İndir</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <a href="{{ route('admin.analysis-requests.projects.show', $analysisRequest->project_id) }}" class="kt-btn kt-btn-sm kt-btn-light mt-5">Süreç geçmişini aç</a>
            </article>
        @empty
            <div class="kt-card p-8 text-center text-muted-foreground">Henüz görüntülenecek ek analiz talebi yok.</div>
        @endforelse

        <div class="mt-6">{{ $analysisRequests->links() }}</div>
    </div>
@endsection
