@if(($adminNotificationsReady ?? false) && Route::has('admin.notifications.index'))
    <a
        href="{{ route('admin.notifications.index') }}"
        class="kt-btn kt-btn-ghost kt-btn-icon size-8 hover:bg-background hover:[&_i]:text-primary relative"
        title="Bildirim Merkezi"
        aria-label="Bildirim Merkezi"
    >
        <i class="ki-outline ki-notification-status text-xl"></i>
        @if(($adminUnreadNotificationCount ?? 0) > 0)
            <span class="absolute -end-1 -top-1 min-w-4 rounded-full bg-danger px-1 text-center text-[10px] font-semibold leading-4 text-white">
                {{ ($adminUnreadNotificationCount ?? 0) > 99 ? '99+' : $adminUnreadNotificationCount }}
            </span>
        @endif
    </a>
@endif
