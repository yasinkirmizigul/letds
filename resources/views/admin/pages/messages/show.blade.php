@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[90%]">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="{{ \App\Models\ContactMessage::priorityBadgeClass($message->priority) }}">
                            {{ \App\Models\ContactMessage::priorityLabel($message->priority) }}
                        </span>
                        <span class="{{ \App\Models\ContactMessage::senderTypeBadgeClass($message->sender_type) }}">
                            {{ \App\Models\ContactMessage::senderTypeLabel($message->sender_type) }}
                        </span>
                        <span class="kt-badge kt-badge-sm {{ $message->isRead() ? 'kt-badge-light-success' : 'kt-badge-light-warning' }}">
                            {{ $message->isRead() ? 'Okundu' : 'Okunmadı' }}
                        </span>
                    </div>

                    <div>
                        <h1 class="text-2xl font-semibold text-foreground">{{ $message->subject }}</h1>
                        <p class="text-sm text-muted-foreground">
                            Gönderim: {{ $message->created_at->format('d.m.Y H:i') }}
                            @if($message->read_at)
                                • Okunma: {{ $message->read_at->format('d.m.Y H:i') }}
                            @endif
                        </p>
                    </div>
                </div>

                <a href="{{ route('admin.messages.index') }}" class="kt-btn kt-btn-light">
                    Listeye Dön
                </a>
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.75fr)]">
                <div class="grid gap-5">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <h3 class="kt-card-title">Mesaj İçeriği</h3>
                        </div>
                        <div class="kt-card-content p-6">
                            <div class="rounded-3xl border border-border bg-muted/10 p-5 whitespace-pre-line text-sm leading-7 text-foreground">
                                {{ $message->message }}
                            </div>
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <h3 class="kt-card-title">Kayıt Meta Bilgileri</h3>
                        </div>
                        <div class="kt-card-content p-6 grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-border bg-white px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">İletişim tercihi</div>
                                <div class="mt-2 font-medium text-foreground">{{ $message->preferredChannelsLabel() }}</div>
                            </div>
                            <div class="rounded-2xl border border-border bg-white px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">IP adresi</div>
                                <div class="mt-2 font-medium text-foreground">{{ $message->ip_address ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-border bg-white px-4 py-4 md:col-span-2">
                                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">User Agent</div>
                                <div class="mt-2 break-all text-sm text-foreground">{{ $message->user_agent ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <h3 class="kt-card-title">Gönderen</h3>
                        </div>
                        <div class="kt-card-content p-6 grid gap-4">
                            <div class="rounded-2xl border border-border bg-white px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Ad Soyad</div>
                                <div class="mt-2 font-medium text-foreground">{{ $message->sender_full_name }}</div>
                            </div>
                            <div class="rounded-2xl border border-border bg-white px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">E-posta</div>
                                <div class="mt-2 font-medium text-foreground">{{ $message->sender_email ?: 'Belirtilmedi' }}</div>
                            </div>
                            <div class="rounded-2xl border border-border bg-white px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Telefon</div>
                                <div class="mt-2 font-medium text-foreground">{{ $message->sender_phone ?: 'Belirtilmedi' }}</div>
                            </div>

                            @if($message->member)
                                <div class="rounded-2xl border border-success/20 bg-success/5 px-4 py-4">
                                    <div class="text-xs uppercase tracking-[0.16em] text-success">Üye kaydı ile eşleşti</div>
                                    <div class="mt-2 text-sm text-foreground">
                                        #{{ $message->member->id }} • {{ $message->member->full_name }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <h3 class="kt-card-title">Alıcı</h3>
                        </div>
                        <div class="kt-card-content p-6 grid gap-4">
                            <div class="rounded-2xl border border-border bg-white px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Kullanıcı</div>
                                <div class="mt-2 font-medium text-foreground">{{ $message->recipient_display_name }}</div>
                            </div>
                            <div class="rounded-2xl border border-border bg-white px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">E-posta</div>
                                <div class="mt-2 font-medium text-foreground">{{ $message->recipient?->email ?: 'Kullanıcı kaydı bulunamadı' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
