@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] pb-10">
        <a href="{{ route('admin.analysis-requests.index') }}" class="text-sm font-semibold text-primary">← Analiz çalışmalarına dön</a>
        <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">{{ $project->title }}</h1>
                <p class="mt-2 text-sm text-muted-foreground">{{ $project->member?->full_name }} · Uzman: {{ $project->appointment?->provider?->name ?: 'Atanmadı' }}</p>
            </div>
            <span class="{{ \App\Models\Admin\Project\Project::statusBadgeClass($project->status) }}">{{ \App\Models\Admin\Project\Project::statusLabel($project->status) }}</span>
        </div>

        @includeIf('admin.partials._flash')
        @if($errors->any())<div class="mt-5 rounded-xl border border-danger/30 p-4 text-sm text-danger">{{ $errors->first() }}</div>@endif

        <div class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(19rem,.6fr)]">
            <div class="grid content-start gap-6">
                @include('shared.project-workflow-timeline', ['viewer' => 'backoffice'])

                <section class="kt-card p-6">
                    <h2 class="text-lg font-semibold text-foreground">Paylaşılan belgeler</h2>
                    <div class="mt-4 grid gap-2">
                        @forelse($project->files as $file)
                            <a href="{{ route('admin.analysis-requests.projects.files.download', [$project, $file]) }}" class="flex items-center justify-between gap-3 rounded-xl border border-border px-4 py-3 text-sm hover:border-primary/50">
                                <span class="min-w-0 truncate text-foreground">{{ $file->original_name }}</span>
                                <span class="shrink-0 text-xs text-muted-foreground">{{ $file->member_id ? 'Üye belgesi' : 'Uzman belgesi' }} · İndir</span>
                            </a>
                        @empty
                            <p class="text-sm text-muted-foreground">Henüz belge paylaşılmadı.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="grid content-start gap-6">
                <section class="kt-card p-6">
                    <h2 class="text-lg font-semibold text-foreground">Analiz aşamaları</h2>
                    <ol class="mt-5 grid gap-2">
                        @foreach($workflowSteps as $step)
                            <li class="flex items-center gap-3 rounded-xl border border-border p-3 text-sm {{ $step['is_current'] ? 'bg-primary/10 text-primary' : 'text-muted-foreground' }}">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full {{ $step['is_complete'] || $step['is_current'] ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">{{ $step['is_complete'] ? '✓' : $loop->iteration }}</span>
                                {{ $step['label'] }}
                            </li>
                        @endforeach
                    </ol>
                    @if(! auth()->user()->isAdmin())
                        @php
                            $nextStatus = match($project->status) {
                                \App\Models\Admin\Project\Project::STATUS_APPOINTMENT_DONE => 'dev_pending',
                                \App\Models\Admin\Project\Project::STATUS_DEV_PENDING => 'dev_in_progress',
                                \App\Models\Admin\Project\Project::STATUS_DELIVERED => 'approved',
                                default => null,
                            };
                        @endphp
                        @if($nextStatus)
                            <form method="POST" action="{{ route('admin.analysis-requests.projects.status.update', $project) }}" class="mt-5">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $nextStatus }}">
                                <button type="submit" class="kt-btn kt-btn-primary w-full">{{ \App\Models\Admin\Project\Project::statusLabel($nextStatus) }} aşamasına geçir</button>
                            </form>
                        @endif
                    @endif
                </section>

                @if(! auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.analysis-requests.projects.reports.store', $project) }}" enctype="multipart/form-data" class="kt-card p-6">
                        @csrf
                        <h2 class="text-lg font-semibold text-foreground">Raporu üyeye teslim et</h2>
                        <p class="mt-2 text-sm text-muted-foreground">Yüklediğiniz rapor yönetici onayı beklemeden üyeye açılır. Çalışma otomatik olarak raporlama aşamasına geçer.</p>
                        <input type="file" name="report" class="kt-input mt-4 w-full" required accept=".pdf,.doc,.docx,.xls,.xlsx,.zip">
                        <button type="submit" class="kt-btn kt-btn-primary mt-4 w-full">Raporu paylaş</button>
                    </form>
                @endif

                <section class="kt-card p-6">
                    <h2 class="text-lg font-semibold text-foreground">Ek analiz görüşmeleri</h2>
                    <div class="mt-4 grid gap-5">
                        @forelse($project->analysisRequests as $analysisRequest)
                            <div class="rounded-xl border border-border p-4">
                                <div class="flex flex-wrap justify-between gap-2 text-sm">
                                    <strong>Talep #{{ $analysisRequest->id }}</strong>
                                    <span class="text-muted-foreground">{{ \App\Models\Admin\Project\ProjectAnalysisRequest::statusLabel($analysisRequest->status) }}</span>
                                </div>
                                <p class="mt-3 whitespace-pre-wrap text-sm text-foreground">{{ $analysisRequest->message }}</p>
                                @if(! auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('admin.analysis-requests.reply', $analysisRequest) }}" enctype="multipart/form-data" class="mt-4 grid gap-3">
                                        @csrf
                                        <label for="provider-reply-{{ $analysisRequest->id }}" class="text-sm font-semibold">Üyeye yanıt yaz</label>
                                        <textarea id="provider-reply-{{ $analysisRequest->id }}" name="message" class="kt-input w-full" rows="4" minlength="2" maxlength="5000" required></textarea>
                                        <input type="file" name="documents[]" multiple class="kt-input w-full" aria-label="Yanıta belgeler ekle" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.zip,.jpg,.jpeg,.png,.webp,.txt">
                                        <label class="flex items-center gap-2 text-xs text-muted-foreground"><input type="checkbox" name="complete" value="1"> Bu yanıtla talebi sonuçlandır</label>
                                        <button type="submit" class="kt-btn kt-btn-primary">Yanıtı gönder</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">Henüz ek analiz talebi yok.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
