@if($project->analysisRequests->isNotEmpty())
    <div class="kt-card mt-6 overflow-hidden">
        <div class="kt-card-header py-5">
            <div>
                <h3 class="kt-card-title">Ek Analiz Talepleri</h3>
                <div class="text-sm text-muted-foreground">Rapor sonrası üyeden gelen açıklamalar ve ek belgeler.</div>
            </div>
            <span class="kt-badge kt-badge-sm kt-badge-light-primary">{{ $project->analysisRequests->count() }} talep</span>
        </div>
        <div class="kt-card-content grid gap-4 p-6">
            @foreach($project->analysisRequests as $analysisRequest)
                <div class="rounded-2xl border border-border p-5">
                    <div class="flex flex-wrap justify-between gap-2 text-sm">
                        <strong>{{ $analysisRequest->member?->full_name ?: 'Üye' }}</strong>
                        <span class="text-muted-foreground">{{ $analysisRequest->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    <p class="mt-3 whitespace-pre-wrap text-sm leading-6">{{ $analysisRequest->message }}</p>
                    @if($analysisRequest->files->isNotEmpty())
                        <div class="mt-3 grid gap-2">
                            <strong class="text-xs">Ek belgeler ({{ $analysisRequest->files->count() }})</strong>
                            @foreach($analysisRequest->files as $requestFile)
                                <a class="flex items-center justify-between gap-3 rounded-xl border border-border px-3 py-2 text-sm hover:border-primary/40" href="{{ route('admin.projects.files.download', ['project' => $project, 'projectFile' => $requestFile]) }}">
                                    <span class="min-w-0 truncate">{{ $requestFile->original_name }}</span>
                                    <span class="shrink-0 text-xs text-muted-foreground">{{ $requestFile->sizeLabel() }} · İndir</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <span class="kt-badge kt-badge-light-primary mt-3">{{ \App\Models\Admin\Project\ProjectAnalysisRequest::statusLabel($analysisRequest->status) }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
