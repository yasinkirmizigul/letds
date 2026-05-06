@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6">
        @include('admin.partials._flash')

        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="{{ \App\Models\ContactMessage::priorityBadgeClass($message->priority) }}">
                        {{ \App\Models\ContactMessage::priorityLabel($message->priority) }}
                    </span>
                    <span class="{{ \App\Models\ContactMessage::statusBadgeClass($message->status) }}">
                        {{ \App\Models\ContactMessage::statusLabel($message->status) }}
                    </span>
                    <span class="{{ \App\Models\ContactMessage::senderTypeBadgeClass($message->sender_type) }}">
                        {{ \App\Models\ContactMessage::senderTypeLabel($message->sender_type) }}
                    </span>
                    <span class="kt-badge kt-badge-sm {{ $message->isRead() ? 'kt-badge-light-success' : 'kt-badge-light-warning' }}">
                        {{ $message->isRead() ? 'Okundu' : 'Okunmadı' }}
                    </span>
                    @if($message->isOverdue())
                        <span class="kt-badge kt-badge-sm kt-badge-light-danger">SLA geçti</span>
                    @endif
                </div>

                <div>
                    <h1 class="text-2xl font-semibold text-foreground">{{ $message->subject }}</h1>
                    <p class="text-sm text-muted-foreground">
                        Gönderim: {{ $message->created_at->format('d.m.Y H:i') }}
                        @if($message->read_at)
                            · Okunma: {{ $message->read_at->format('d.m.Y H:i') }}
                        @endif
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.messages.index') }}" class="kt-btn kt-btn-light">
                Listeye Dön
            </a>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(360px,0.8fr)]">
            <div class="grid gap-5">
                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <h3 class="kt-card-title">Mesaj İçeriği</h3>
                    </div>
                    <div class="kt-card-content p-6">
                        <div class="rounded-3xl app-surface-card app-surface-card--soft p-5 whitespace-pre-line text-sm leading-7 text-foreground">
                            {{ $message->message }}
                        </div>
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <h3 class="kt-card-title">İş Akışı</h3>
                    </div>
                    <div class="kt-card-content p-6">
                        <form method="POST" action="{{ route('admin.messages.workflow', $message) }}" class="grid gap-4" data-ajax-stay="true">
                            @csrf
                            @method('PATCH')

                            <div class="grid gap-4 lg:grid-cols-3">
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Durum</label>
                                    <select name="status" class="kt-select">
                                        @foreach($statusOptions as $statusKey => $statusOption)
                                            <option value="{{ $statusKey }}" @selected(old('status', $message->status) === $statusKey)>
                                                {{ $statusOption['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid gap-2">
                                    <label class="kt-form-label">Atanan Kullanıcı</label>
                                    <select name="assigned_user_id" class="kt-select">
                                        <option value="">Atanmamış</option>
                                        @foreach($assigneeOptions as $assignee)
                                            <option value="{{ $assignee->id }}" @selected((int) old('assigned_user_id', $message->assigned_user_id) === (int) $assignee->id)>
                                                {{ $assignee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid gap-2">
                                    <label class="kt-form-label">Son Tarih</label>
                                    <div class="kt-input w-full">
                                        <i class="ki-outline ki-calendar"></i>
                                        <input
                                            name="due_at"
                                            class="grow"
                                            type="text"
                                            readonly
                                            placeholder="GG.AA.YYYY SS:DD"
                                            value="{{ old('due_at', optional($message->due_at)->format('d.m.Y H:i')) }}"
                                            data-app-date-picker="true"
                                            data-app-date-mode="datetime"
                                            data-app-date-format="DD.MM.YYYY HH:mm"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <label class="kt-form-label">Etiketler</label>
                                <input name="tags" class="kt-input" value="{{ old('tags', implode(', ', $message->tags ?? [])) }}" placeholder="satış, destek, teklif">
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="grid gap-2">
                                    <label class="kt-form-label">İç Not</label>
                                    <textarea name="internal_note" rows="5" class="kt-textarea" placeholder="Sadece panel kullanıcıları görür.">{{ old('internal_note', $message->internal_note) }}</textarea>
                                </div>
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Çözüm Notu</label>
                                    <textarea name="resolution_note" rows="5" class="kt-textarea" placeholder="Talep çözüldüğünde kısa sonuç notu.">{{ old('resolution_note', $message->resolution_note) }}</textarea>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="text-xs leading-5 text-muted-foreground">
                                    İlk yanıt: {{ $message->first_response_at?->format('d.m.Y H:i') ?: 'Henüz yok' }}
                                    · Çözüm: {{ $message->resolved_at?->format('d.m.Y H:i') ?: 'Henüz yok' }}
                                </div>
                                <button type="submit" class="kt-btn kt-btn-primary">
                                    İş Akışını Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <h3 class="kt-card-title">Kayıt Meta Bilgileri</h3>
                    </div>
                    <div class="kt-card-content p-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl app-surface-card px-4 py-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">İletişim tercihi</div>
                            <div class="mt-2 font-medium text-foreground">{{ $message->preferredChannelsLabel() }}</div>
                        </div>
                        <div class="rounded-2xl app-surface-card px-4 py-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">IP adresi</div>
                            <div class="mt-2 font-medium text-foreground">{{ $message->ip_address ?: '-' }}</div>
                        </div>
                        <div class="rounded-2xl app-surface-card px-4 py-4 md:col-span-2">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">User Agent</div>
                            <div class="mt-2 break-all text-sm text-foreground">{{ $message->user_agent ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 self-start xl:sticky xl:top-6">
                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <h3 class="kt-card-title">Gönderen</h3>
                    </div>
                    <div class="kt-card-content p-6 grid gap-4">
                        <div class="rounded-2xl app-surface-card px-4 py-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Ad Soyad</div>
                            <div class="mt-2 font-medium text-foreground">{{ $message->sender_full_name }}</div>
                        </div>
                        <div class="rounded-2xl app-surface-card px-4 py-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">E-posta</div>
                            <div class="mt-2 font-medium text-foreground">{{ $message->sender_email ?: 'Belirtilmedi' }}</div>
                        </div>
                        <div class="rounded-2xl app-surface-card px-4 py-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Telefon</div>
                            <div class="mt-2 font-medium text-foreground">{{ $message->sender_phone ?: 'Belirtilmedi' }}</div>
                        </div>

                        @if($message->member)
                            <div class="rounded-2xl app-surface-card app-surface-card--success px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-success">Üye kaydı ile eşleşti</div>
                                <div class="mt-2 text-sm text-foreground">
                                    #{{ $message->member->id }} · {{ $message->member->full_name }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <h3 class="kt-card-title">Sahiplik</h3>
                    </div>
                    <div class="kt-card-content p-6 grid gap-4">
                        <div class="rounded-2xl app-surface-card px-4 py-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Alıcı</div>
                            <div class="mt-2 font-medium text-foreground">{{ $message->recipient_display_name }}</div>
                            <div class="mt-1 text-sm text-muted-foreground">{{ $message->recipient?->email ?: 'Kullanıcı kaydı bulunamadı' }}</div>
                        </div>
                        <div class="rounded-2xl app-surface-card px-4 py-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Atanan</div>
                            <div class="mt-2 font-medium text-foreground">{{ $message->assignedUser?->name ?: 'Atanmamış' }}</div>
                            <div class="mt-1 text-sm text-muted-foreground">{{ $message->assignedUser?->email ?: 'Sorumlu seçilmedi' }}</div>
                        </div>
                        @if($message->closedBy)
                            <div class="rounded-2xl app-surface-card px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Kapatan</div>
                                <div class="mt-2 font-medium text-foreground">{{ $message->closedBy->name }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
