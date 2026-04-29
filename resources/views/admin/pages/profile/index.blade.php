@extends('admin.layouts.main.app')

@section('content')
    @php
        $u = $profileUser ?? $user ?? auth()->user();
        $canEditProfile = (bool) ($canEditProfile ?? auth()->id() === $u?->id);
        $avatarUrl = $u && method_exists($u, 'avatarUrl') ? $u->avatarUrl() : asset('assets/media/blank.png');
        $fullName = $u->name ?: 'Kullanıcı';
        $title = $u->title ?: 'Unvan bilgisi eklenmemiş';
        $email = $u->email ?: '—';
        $phone = $u->phone ?: '—';
        $company = $u->company ?: '—';
        $location = $u->location ?: '—';
        $bio = $u->bio ?: null;
        $skills = method_exists($u, 'skillTags') ? $u->skillTags() : [];
        $completion = method_exists($u, 'profileCompletionPercentage') ? $u->profileCompletionPercentage() : 0;
        $roles = $u->roles ?? collect();
        $topRole = $roles->sortByDesc('priority')->first();
        $isVerified = !is_null($u->email_verified_at);
        $isActive = (bool) ($u->is_active ?? false);
    @endphp

    <style>
        .hero-bg { background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}'); }
        .dark .hero-bg { background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}'); }
    </style>

    <div class="bg-center bg-cover bg-no-repeat hero-bg">
        <div class="kt-container-fixed max-w-[90%]">
            <div class="flex flex-col items-center gap-3 py-5 lg:pt-6 lg:pb-11">
                <img class="rounded-full border-3 {{ $isActive ? 'border-green-500' : 'border-danger' }} size-[104px] shrink-0 object-cover bg-background"
                     src="{{ $avatarUrl }}"
                     alt="{{ $fullName }} avatarı">

                <div class="flex flex-col items-center gap-1.5 text-center">
                    <div class="flex items-center gap-2">
                        <div class="text-xl leading-6 font-semibold text-mono">{{ $fullName }}</div>
                        @if($isVerified)
                            <i class="ki-filled ki-verify text-primary text-lg" title="E-posta doğrulanmış"></i>
                        @endif
                    </div>

                    <div class="text-sm text-secondary-foreground">{{ $title }}</div>

                    <div class="flex flex-wrap justify-center gap-2">
                        @if($topRole)
                            <span class="kt-badge kt-badge-light-primary">{{ $topRole->name }}</span>
                        @endif

                        @if($isActive)
                            <span class="kt-badge kt-badge-success kt-badge-outline">Aktif</span>
                        @else
                            <span class="kt-badge kt-badge-danger kt-badge-outline">Pasif</span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap justify-center gap-2 lg:gap-5 text-sm">
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-abstract-41 text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">{{ $company }}</span>
                    </div>

                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-geolocation text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">{{ $location }}</span>
                    </div>

                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-sms text-muted-foreground text-sm"></i>
                        <a class="text-secondary-foreground font-medium hover:text-primary" href="mailto:{{ $email }}">
                            {{ $email }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kt-container-fixed max-w-[90%]" data-page="profile.index">
        <div class="flex items-center flex-wrap justify-between border-b border-b-border gap-3 lg:gap-6 mb-5 lg:mb-10 py-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-mono">Profil Detayı</h1>
                <p class="text-sm text-secondary-foreground">
                    {{ $canEditProfile ? 'Kişisel profil bilgilerini ve görünür alanlarını buradan takip edebilirsin.' : 'Bu profil süper admin için salt okunur olarak görüntüleniyor.' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if(!$canEditProfile)
                    <span class="kt-badge kt-badge-light-warning">Salt okunur</span>
                    <a class="kt-btn kt-btn-light" href="{{ route('admin.users.index') }}">
                        <i class="ki-filled ki-arrow-left"></i>
                        Kullanıcılara Dön
                    </a>
                @else
                    <a class="kt-btn kt-btn-primary" href="{{ route('admin.profile.edit') }}">
                        <i class="ki-filled ki-user-edit"></i>
                        Profili Düzenle
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="kt-container-fixed max-w-[90%]">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Hakkında</h3>
                        </div>
                        <div class="kt-card-content pt-4 pb-3">
                            <table class="kt-table table-auto kt-table-border w-full">
                                <tbody>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-3.5 pe-3">E-posta</td>
                                    <td class="text-sm text-mono pb-3.5">
                                        <a class="text-foreground hover:text-primary" href="mailto:{{ $email }}">{{ $email }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Telefon</td>
                                    <td class="text-sm text-mono pb-3.5">{{ $phone }}</td>
                                </tr>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Şirket</td>
                                    <td class="text-sm text-mono pb-3.5">{{ $company }}</td>
                                </tr>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Konum</td>
                                    <td class="text-sm text-mono pb-3.5">{{ $location }}</td>
                                </tr>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Durum</td>
                                    <td class="text-sm text-mono pb-3.5">
                                        @if($isActive)
                                            <span class="kt-badge kt-badge-success kt-badge-outline">Aktif</span>
                                        @else
                                            <span class="kt-badge kt-badge-danger kt-badge-outline">Pasif</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Doğrulama</td>
                                    <td class="text-sm text-mono pb-3.5">
                                        @if($isVerified)
                                            <span class="kt-badge kt-badge-primary kt-badge-outline">E-posta doğrulandı</span>
                                        @else
                                            <span class="kt-badge kt-badge-outline">Doğrulanmadı</span>
                                        @endif
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Yetenekler</h3>
                        </div>
                        <div class="kt-card-content">
                            @if($skills !== [])
                                <div class="flex flex-wrap gap-2.5">
                                    @foreach($skills as $skill)
                                        <span class="kt-badge kt-badge-outline">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-sm text-muted-foreground">
                                    Henüz yetenek eklenmemiş.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Bağlantılar</h3>
                        </div>
                        <div class="kt-card-content flex flex-col gap-3">
                            @if($u->website_url)
                                <a class="kt-link kt-link-underlined kt-link-dashed" href="{{ $u->website_url }}" target="_blank" rel="noopener">
                                    <i class="ki-filled ki-global me-1"></i>
                                    Web Sitesi
                                </a>
                            @endif

                            @if($u->linkedin_url)
                                <a class="kt-link kt-link-underlined kt-link-dashed" href="{{ $u->linkedin_url }}" target="_blank" rel="noopener">
                                    <i class="ki-filled ki-people me-1"></i>
                                    LinkedIn
                                </a>
                            @endif

                            @if(!$u->website_url && !$u->linkedin_url)
                                <div class="text-sm text-muted-foreground">Bağlantı bilgisi eklenmemiş.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    <div class="kt-card">
                        <div class="kt-card-content px-7 py-6 lg:px-10 lg:py-7.5">
                            <div class="flex flex-wrap md:flex-nowrap items-center justify-between gap-6">
                                <div class="flex flex-col gap-3 max-w-2xl">
                                    <div class="flex items-center gap-2">
                                        <span class="kt-badge kt-badge-light-primary">Profil Tamamlama</span>
                                        <span class="text-sm font-medium text-mono">%{{ $completion }}</span>
                                    </div>
                                    <h2 class="text-xl font-semibold text-mono">
                                        Profesyonel profil görünümü hazır.
                                    </h2>
                                    <p class="text-sm text-secondary-foreground leading-5.5">
                                        Unvan, iletişim, yetenekler ve biyografi alanları artık gerçek profil verisi olarak yönetilir. Eksik kalan alanlar tamamlandıkça profil kartı daha güçlü görünür.
                                    </p>
                                    <div class="kt-progress kt-progress-primary h-[5px] max-w-xl">
                                        <div class="kt-progress-indicator" style="width: {{ $completion }}%"></div>
                                    </div>
                                </div>

                                <img alt="Profil illüstrasyonu" class="dark:hidden max-h-[150px]" src="{{ asset('assets/media/illustrations/1.svg') }}">
                                <img alt="Profil illüstrasyonu" class="light:hidden max-h-[150px]" src="{{ asset('assets/media/illustrations/1-dark.svg') }}">
                            </div>
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Biyografi</h3>
                        </div>
                        <div class="kt-card-content">
                            @if($bio)
                                <p class="text-sm text-secondary-foreground leading-6 whitespace-pre-line">{{ $bio }}</p>
                            @else
                                <div class="rounded-xl border border-dashed border-border bg-muted/30 p-5 text-sm text-muted-foreground">
                                    Biyografi bilgisi eklenmemiş.
                                    @if($canEditProfile)
                                        Profilini düzenleyerek kısa bir tanıtım metni ekleyebilirsin.
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Rol ve Erişim Özeti</h3>
                        </div>
                        <div class="kt-card-content">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="rounded-xl border border-border p-4">
                                    <div class="text-xs text-muted-foreground mb-1">Ana Rol</div>
                                    <div class="text-sm font-semibold text-mono">{{ $topRole?->name ?: 'Rol atanmadı' }}</div>
                                </div>

                                <div class="rounded-xl border border-border p-4">
                                    <div class="text-xs text-muted-foreground mb-1">Kayıt Tarihi</div>
                                    <div class="text-sm font-semibold text-mono">{{ $u->created_at?->format('d.m.Y') ?: '—' }}</div>
                                </div>

                                <div class="rounded-xl border border-border p-4">
                                    <div class="text-xs text-muted-foreground mb-1">Son Güncelleme</div>
                                    <div class="text-sm font-semibold text-mono">{{ $u->updated_at?->format('d.m.Y') ?: '—' }}</div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 mt-5">
                                @forelse($roles as $role)
                                    <span class="kt-badge kt-badge-light">{{ $role->name }}</span>
                                @empty
                                    <span class="text-sm text-muted-foreground">Bu kullanıcıya rol atanmamış.</span>
                                @endforelse
                            </div>

                            @if(!$canEditProfile)
                                <div class="mt-5 rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm text-secondary-foreground">
                                    Bu ekranda profil bilgileri yalnızca görüntülenir. Kişisel profil alanlarını sadece hesabın sahibi düzenleyebilir.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
