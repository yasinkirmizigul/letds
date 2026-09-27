@php
    $isMemberView = $viewer === 'member';
    $timelineFiles = $project->files->keyBy('id');
@endphp
<section class="rounded-3xl border border-border bg-background p-6 md:p-8" aria-label="Analiz süreç geçmişi">
    <div class="mb-7">
        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Süreç geçmişi</span>
        <h2 class="mt-2 text-2xl font-semibold text-foreground">Her adım tek çizgide</h2>
        <p class="mt-2 text-sm text-muted-foreground">Üye ve uzman arasındaki belge, rapor ve mesajlar tarih sırasıyla kaydedilir.</p>
    </div>
    <ol class="relative ml-3 border-l-2 border-border pl-7">
        @forelse($project->workflowEvents as $event)
            @php
                $eventFiles = collect($event->data['file_ids'] ?? [])->map(fn ($id) => $timelineFiles->get((int) $id))->filter();
                $isOwn = $isMemberView ? $event->actor_type === 'member' : $event->actor_type === 'provider';
            @endphp
            <li class="relative mb-7 last:mb-0">
                <span class="absolute -left-[2.3rem] top-1 grid size-4 place-items-center rounded-full border-2 border-background {{ $isOwn ? 'bg-primary' : 'bg-foreground' }}" aria-hidden="true"></span>
                <div class="rounded-2xl border border-border bg-muted/20 p-4 md:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <strong class="text-sm text-foreground">{{ $event->label() }}</strong>
                        <time class="text-xs text-muted-foreground" datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('d.m.Y H:i') }}</time>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ match($event->actor_type) { 'member' => 'Üye', 'provider' => 'Uzman', 'admin' => 'Yönetici', default => 'Sistem' } }}</p>
                    @if($event->body)
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-foreground">{{ $event->body }}</p>
                    @endif
                    @if($eventFiles->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($eventFiles as $eventFile)
                                <a class="rounded-lg border border-border bg-background px-3 py-2 text-xs font-medium text-primary hover:border-primary" href="{{ $isMemberView ? route('member.projects.files.download', ['project' => $project, 'projectFile' => $eventFile, 'site_locale' => $siteCurrentLocale]) : route('admin.analysis-requests.projects.files.download', [$project, $eventFile]) }}">{{ $eventFile->original_name }} ↗</a>
                            @endforeach
                        </div>
                    @endif
                    @if($event->analysis_request_id)
                        <span class="mt-3 inline-block text-xs text-muted-foreground">Ek analiz #{{ $event->analysis_request_id }}</span>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-sm text-muted-foreground">Henüz süreç kaydı bulunmuyor.</li>
        @endforelse
    </ol>
</section>
