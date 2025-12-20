@php
    $u = auth()->user();

    $displayName = $u?->name
        ?? ($u?->full_name ?? null)
        ?? ($u?->email ? \Illuminate\Support\Str::before($u->email, '@') : 'Kullanıcı');

    $email = $u?->email ?? '';

    $avatarUrl = $u ? $u->avatarUrl() : asset('assets/media/avatars/300-2.png');

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

    <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
        <div class="flex items-center justify-between gap-1.5 px-2.5 py-1.5">
            <div class="flex items-center gap-2">
                <img alt="{{ $displayName }}"
                     class="size-9 shrink-0 rounded-full border-2 border-green-500"
                     src="{{ $avatarUrl }}" />
                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-semibold leading-none text-foreground">
                        {{ $displayName }}
                    </span>
                    @if($email)
                        <a class="hover:text-primary text-xs font-medium leading-none text-secondary-foreground"
                           href="mailto:{{ $email }}">
                            {{ $email }}
                        </a>
                    @endif
                </div>
            </div>

            <span class="kt-badge kt-badge-sm kt-badge-primary kt-badge-outline">
                {{ $badgeText }}
            </span>
        </div>

        <ul class="kt-dropdown-menu-sub">
            <li><div class="kt-dropdown-menu-separator"></div></li>

            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('admin.profile.edit') }}">
                    <i class="ki-filled ki-profile-circle"></i>
                    Profilim
                </a>
            </li>

            <li><div class="kt-dropdown-menu-separator"></div></li>
        </ul>

        <div class="mb-2.5 flex flex-col gap-3.5 px-2.5 pt-1.5">
            <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2">
                    <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                    <span class="text-2sm font-medium">Koyu Mod</span>
                </span>
                <input class="kt-switch"
                       data-kt-theme-switch-state="dark"
                       data-kt-theme-switch-toggle="true"
                       name="check"
                       type="checkbox"
                       value="1" />
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline w-full justify-center">
                    Çıkış
                </button>
            </form>
        </div>
    </div>
</div>
<!-- End of User -->
