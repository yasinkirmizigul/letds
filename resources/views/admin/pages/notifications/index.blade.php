@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Operasyon</span>
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Bildirim Merkezi</h1>
                    <div class="text-sm text-muted-foreground">
                        Mesaj, randevu, sipariş, stok ve sistem uyarılarını tek yerden takip edin.
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.notifications.readAll') }}" data-ajax-stay="true">
                @csrf
                <button type="submit" class="kt-btn kt-btn-light-primary">
                    <i class="ki-filled ki-check-circle"></i>
                    Tümünü okundu yap
                </button>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Aktif</div>
                <div class="mt-2 text-3xl font-semibold text-foreground">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Okunmamış</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['unread'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Kapatılan</div>
                <div class="mt-2 text-3xl font-semibold text-muted-foreground">{{ $stats['dismissed'] ?? 0 }}</div>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5 flex-wrap gap-4">
                <div>
                    <h3 class="kt-card-title">Bildirimler</h3>
                    <div class="text-sm text-muted-foreground">İş yoğunluğunu modüle ve okunma durumuna göre süzün.</div>
                </div>

                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <select name="status" class="kt-select w-full md:w-[180px]">
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="unread" @selected($status === 'unread')>Okunmamış</option>
                        <option value="read" @selected($status === 'read')>Okunmuş</option>
                        <option value="dismissed" @selected($status === 'dismissed')>Kapatılan</option>
                    </select>

                    <select name="type" class="kt-select w-full md:w-[190px]">
                        @foreach($typeOptions as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" @selected($type === $typeKey)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="kt-btn kt-btn-light">Filtrele</button>
                </form>
            </div>

            <div class="kt-card-content grid gap-4 p-6">
                @forelse($notifications as $notification)
                    <div class="rounded-[28px] app-surface-card p-5 {{ $notification->isUnread() ? 'border-primary/40' : '' }}">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="flex gap-4">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                    <i class="{{ $notification->iconClass() }} text-xl"></i>
                                </div>
                                <div class="grid gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="{{ $notification->severityBadgeClass() }}">{{ $notification->severityLabel() }}</span>
                                        @if($notification->isUnread())
                                            <span class="kt-badge kt-badge-sm kt-badge-light-warning">Yeni</span>
                                        @endif
                                        <span class="text-xs text-muted-foreground">{{ $notification->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <div>
                                        <div class="text-lg font-semibold text-foreground">{{ $notification->title }}</div>
                                        @if($notification->body)
                                            <div class="mt-1 text-sm leading-6 text-muted-foreground">{{ $notification->body }}</div>
                                        @endif
                                    </div>
                                    @if($notification->action_url)
                                        <a href="{{ $notification->action_url }}" class="w-fit text-sm font-medium text-primary">
                                            {{ $notification->action_label ?: 'Detaya git' }}
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if($notification->isUnread())
                                    <form method="POST" action="{{ route('admin.notifications.read', $notification) }}" data-ajax-stay="true">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-light">Okundu</button>
                                    </form>
                                @endif
                                @if(!$notification->dismissed_at)
                                    <form method="POST" action="{{ route('admin.notifications.dismiss', $notification) }}" data-ajax-stay="true">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-light-danger">Kapat</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-border px-6 py-12 text-center">
                        <i class="ki-outline ki-notification-status text-4xl text-muted-foreground"></i>
                        <div class="mt-3 text-lg font-semibold text-foreground">Bildirim bulunmuyor.</div>
                        <div class="mt-2 text-sm text-muted-foreground">Kullanılan modüllerden gelen yeni işler burada toplanacak.</div>
                    </div>
                @endforelse
            </div>

            @if($notifications->hasPages())
                <div class="kt-card-footer">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
