@php
    $u = auth()->user();

    $displayName = $u?->name
        ?? ($u?->full_name ?? null)
        ?? ($u?->email ? \Illuminate\Support\Str::before($u->email, '@') : 'Kullanıcı');

    $email = $u?->email ?? '';

    $avatarUrl = $u ? $u->avatarUrl() : asset('assets/admin/media/avatars/300-2.png');

    // roles eager-load varsa N+1 olmaz (AppServiceProvider admin tarafında loadMissing yapıyor)
    $badgeText = $u ? $u->badgeLabel() : 'Guest';
@endphp

    <!-- User -->
<div class="shrink-0"
     data-kt-dropdown="true"
     data-kt-dropdown-offset="10px, 10px"
     data-kt-dropdown-offset-rtl="-20px, 10px"
     data-kt-dropdown-placement="bottom-end"
     data-kt-dropdown-placement-rtl="bottom-start"
     data-kt-dropdown-trigger="click">

    <div class="shrink-0 cursor-pointer" data-kt-dropdown-toggle="true">
        <img alt="{{ $displayName }}"
             class="size-9 shrink-0 rounded-full border-2 border-green-500"
             src="{{ $avatarUrl }}" />
    </div>

    <div class="kt-dropdown-menu admin-user-menu w-[290px] max-w-[calc(100vw-1rem)]" data-kt-dropdown-menu="true" data-admin-user-menu>
        <div class="admin-user-menu__identity">
            <img alt="{{ $displayName }}"
                 class="admin-user-menu__avatar"
                 src="{{ $avatarUrl }}" />
            <div class="admin-user-menu__identity-copy">
                <strong>{{ $displayName }}</strong>
                @if($email)
                    <a href="mailto:{{ $email }}">{{ $email }}</a>
                @endif
            </div>

            <span class="kt-badge kt-badge-sm kt-badge-primary kt-badge-outline admin-user-menu__badge">
                {{ $badgeText }}
            </span>
        </div>

        <div class="admin-user-menu__actions">
            <a class="admin-user-menu__row" href="{{ route('admin.profile.index') }}">
                <span class="admin-user-menu__icon"><i class="ki-filled ki-profile-circle"></i></span>
                <span class="admin-user-menu__copy">
                    <strong>Profilim</strong>
                    <small>Hesap bilgilerini yönet</small>
                </span>
                <i class="ki-filled ki-right admin-user-menu__chevron"></i>
            </a>

            <label class="admin-user-menu__row admin-user-theme-control" for="admin_user_theme_switch" data-admin-user-theme-control>
                <span class="admin-user-theme-control__content admin-user-theme-control__light">
                    <span class="admin-user-menu__icon admin-user-theme-control__icon"><i class="ki-filled ki-moon"></i></span>
                    <span class="admin-user-menu__copy">
                        <strong>Koyu moda geç</strong>
                        <small>Göz yormayan koyu görünüm</small>
                    </span>
                </span>
                <span class="admin-user-theme-control__content admin-user-theme-control__dark">
                    <span class="admin-user-menu__icon admin-user-theme-control__icon"><i class="ki-filled ki-sun"></i></span>
                    <span class="admin-user-menu__copy">
                        <strong>Açık moda geç</strong>
                        <small>Aydınlık panel görünümü</small>
                    </span>
                </span>
                <input id="admin_user_theme_switch"
                       class="kt-switch admin-user-theme-switch"
                       data-kt-theme-switch-state="dark"
                       data-kt-theme-switch-toggle="true"
                       name="theme_mode"
                       type="checkbox"
                       aria-label="Açık ve koyu tema arasında geçiş yap"
                       value="1" />
            </label>

            <form method="POST" action="{{ route('logout') }}" class="admin-user-menu__form">
                @csrf
                <button type="submit" class="admin-user-menu__row admin-user-menu__logout">
                    <span class="admin-user-menu__icon"><i class="ki-filled ki-exit-right"></i></span>
                    <span class="admin-user-menu__copy">
                        <strong>Güvenli çıkış</strong>
                        <small>Oturumu sonlandır</small>
                    </span>
                    <i class="ki-filled ki-right admin-user-menu__chevron"></i>
                </button>
            </form>
        </div>
    </div>
</div>
<!-- End of User -->
