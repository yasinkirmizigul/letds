@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[90%] dashboard-shell"
         data-page="dash.index"
         data-monthly-chart='@json($monthlyActivity)'
         data-action-chart='@json($actionChart)'
         data-schedule-chart='@json($scheduleChart)'>

        <div class="grid gap-5 lg:gap-7.5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Dashboard</h1>
                    <div class="text-sm text-muted-foreground">
                        Panelde hangi blokların görüneceğini buradan yönetebilirsin.
                    </div>
                </div>

                <a href="{{ route('admin.dashboard.manage') }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-setting-2"></i>
                    Dashboard yönetimi
                </a>
            </div>

            @unless($hasVisibleDashboardSection)
                <section class="kt-card overflow-hidden">
                    <div class="kt-card-content px-6 py-10 text-center">
                        <div class="mx-auto inline-flex size-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <i class="ki-filled ki-setting-2 text-2xl"></i>
                        </div>
                        <h2 class="mt-5 text-lg font-semibold text-foreground">Tüm dashboard blokları gizlenmiş durumda</h2>
                        <p class="mx-auto mt-3 max-w-[60ch] text-sm leading-6 text-muted-foreground">
                            İstersen dashboard yönetimi ekranından blokları tekrar açabilir veya varsayılan düzene geri dönebilirsin.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('admin.dashboard.manage') }}" class="kt-btn kt-btn-primary">
                                Dashboard bloklarını yönet
                            </a>
                        </div>
                    </div>
                </section>
            @endunless

            @if($dashboardSectionVisibility['hero_overview'] ?? false)
                <section class="dashboard-hero kt-card">
                    <div class="dashboard-hero__orb dashboard-hero__orb--primary"></div>
                    <div class="dashboard-hero__orb dashboard-hero__orb--secondary"></div>

                    <div class="kt-card-content p-6 lg:p-8">
                        <div class="grid gap-6 xl:grid-cols-[1.25fr,.75fr] xl:items-start">
                            <div class="relative z-[1]">
                                <div class="dashboard-kicker">Yönetim merkezi</div>
                                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-foreground lg:text-3xl">
                                    {{ $greeting }}, operasyon nabzı burada.
                                </h2>
                                <p class="mt-3 max-w-[68ch] text-sm leading-6 text-muted-foreground lg:text-base">
                                    {{ $heroSummary }}
                                </p>

                                <div class="mt-5 flex flex-wrap items-center gap-2">
                                    <span class="dashboard-chip">
                                        <i class="ki-filled ki-calendar-8 text-[13px]"></i>
                                        {{ $nowLabel }}
                                    </span>
                                    <span class="dashboard-chip">
                                        <i class="ki-filled ki-notification-status text-[13px]"></i>
                                        {{ $focusTotal }} odak işi
                                    </span>
                                    <span class="dashboard-chip">
                                        <i class="ki-filled ki-element-11 text-[13px]"></i>
                                        {{ count($moduleCards) }} hızlı modül
                                    </span>
                                </div>

                                @if(count($quickActions))
                                    <div class="mt-6 flex flex-wrap gap-3">
                                        @foreach($quickActions as $action)
                                            <a href="{{ $action['url'] }}" class="kt-btn {{ $action['style'] }}">
                                                <i class="{{ $action['icon'] }}"></i>
                                                {{ $action['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="dashboard-focus-panel">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-foreground">Odak listesi</div>
                                        <div class="text-xs text-muted-foreground">En hızlı aksiyon alınabilecek başlıklar.</div>
                                    </div>
                                    <span class="kt-badge kt-badge-light-primary">{{ count($focusItems) }} kayıt</span>
                                </div>

                                @if(count($focusItems))
                                    <div class="mt-4 grid gap-3">
                                        @foreach($focusItems as $item)
                                            <a href="{{ $item['url'] }}"
                                               class="dashboard-focus-item"
                                               style="--dashboard-accent: {{ $item['accent'] }};">
                                                <span class="dashboard-focus-item__icon">
                                                    <i class="{{ $item['icon'] }}"></i>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="flex items-center justify-between gap-3">
                                                        <span class="truncate font-medium text-foreground">{{ $item['label'] }}</span>
                                                        <span class="dashboard-focus-item__count">{{ $item['count'] }}</span>
                                                    </span>
                                                    <span class="mt-1 block text-xs text-muted-foreground">{{ $item['hint'] }}</span>
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-4 rounded-2xl border border-dashed border-border bg-background/75 p-4 text-sm text-muted-foreground">
                                        Şu an için acil takip bekleyen bir kayıt görünmüyor. Modüllere hızlı geçiş yapıp günlük akışı buradan yönetebilirsin.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            @if($dashboardSectionVisibility['kpi_overview'] ?? false)
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    @foreach($kpis as $kpi)
                        <article class="dashboard-kpi-card kt-card" style="--dashboard-accent: {{ $kpi['accent'] }};">
                            <div class="kt-card-content p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-sm text-muted-foreground">{{ $kpi['label'] }}</div>
                                        <div class="mt-3 text-3xl font-semibold tracking-tight text-foreground">{{ number_format((int) $kpi['value']) }}</div>
                                    </div>
                                    <span class="dashboard-kpi-card__icon">
                                        <i class="{{ $kpi['icon'] }}"></i>
                                    </span>
                                </div>
                                <div class="mt-4 text-sm leading-6 text-muted-foreground">{{ $kpi['hint'] }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            @if($dashboardSectionVisibility['module_overview'] ?? false)
                <section class="kt-card">
                    <div class="kt-card-header py-5 flex-wrap gap-4">
                        <div>
                            <h3 class="kt-card-title">Hızlı erişim</h3>
                            <div class="text-sm text-muted-foreground">
                                En çok kullandığın yönetim sayfalarına doğrudan geç.
                            </div>
                        </div>
                    </div>
                    <div class="kt-card-content p-5">
                        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                            @foreach($moduleCards as $card)
                                <article class="dashboard-module-card" style="--dashboard-accent: {{ $card['accent'] }};">
                                    <div class="dashboard-module-card__head">
                                        <span class="dashboard-module-card__icon">
                                            <i class="{{ $card['icon'] }}"></i>
                                        </span>
                                        <span class="text-3xl font-semibold tracking-tight text-foreground">
                                            {{ number_format((int) $card['value']) }}
                                        </span>
                                    </div>

                                    <div class="mt-4">
                                        <h4 class="text-base font-semibold text-foreground">{{ $card['title'] }}</h4>
                                        <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ $card['hint'] }}</p>
                                    </div>

                                    <div class="mt-5 flex flex-wrap gap-2">
                                        <a href="{{ $card['route'] }}" class="kt-btn kt-btn-sm kt-btn-light-primary">
                                            Panele git
                                        </a>
                                        @if(!empty($card['action_url']) && !empty($card['action_label']))
                                            <a href="{{ $card['action_url'] }}" class="kt-btn kt-btn-sm kt-btn-light">
                                                {{ $card['action_label'] }}
                                            </a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if($dashboardSectionVisibility['activity_charts'] ?? false)
                <div class="grid gap-5 xl:grid-cols-[1.35fr,.65fr]">
                    <section class="kt-card">
                        <div class="kt-card-header py-5 flex-wrap gap-4">
                            <div>
                                <h3 class="kt-card-title">Son 6 ay üretim ve talep hızı</h3>
                                <div class="text-sm text-muted-foreground">
                                    İçerik, medya ve mesaj akışının aylık yoğunluğunu birlikte izle.
                                </div>
                            </div>
                        </div>
                        <div class="kt-card-content p-5">
                            <div id="dashboardMonthlyChart" class="dashboard-chart"></div>
                        </div>
                    </section>

                    <div class="grid gap-5">
                        <section class="kt-card">
                            <div class="kt-card-header py-5 flex-wrap gap-4">
                                <div>
                                    <h3 class="kt-card-title">Takip dağılımı</h3>
                                    <div class="text-sm text-muted-foreground">
                                        Anlık operasyon yoğunluğunu kategoriler halinde gör.
                                    </div>
                                </div>
                            </div>
                            <div class="kt-card-content p-5">
                                <div id="dashboardActionChart" class="dashboard-chart dashboard-chart--compact"></div>
                            </div>
                        </section>

                        @if($canAppointments)
                            <section class="kt-card">
                                <div class="kt-card-header py-5 flex-wrap gap-4">
                                    <div>
                                        <h3 class="kt-card-title">7 günlük randevu akışı</h3>
                                        <div class="text-sm text-muted-foreground">
                                            Takvimde önümüzdeki bir haftayı hızlı gör.
                                        </div>
                                    </div>
                                </div>
                                <div class="kt-card-content p-5">
                                    <div id="dashboardScheduleChart" class="dashboard-chart dashboard-chart--mini"></div>
                                </div>
                            </section>
                        @endif
                    </div>
                </div>
            @endif

            @if(($dashboardSectionVisibility['recent_messages'] ?? false) || ($dashboardSectionVisibility['upcoming_appointments'] ?? false) || ($dashboardSectionVisibility['recent_content'] ?? false))
                <div class="grid gap-5 xl:grid-cols-3">
                    @if($dashboardSectionVisibility['recent_messages'] ?? false)
                        <section class="kt-card">
                            <div class="kt-card-header py-5 flex-wrap gap-4">
                                <div>
                                    <h3 class="kt-card-title">Son mesajlar</h3>
                                    <div class="text-sm text-muted-foreground">Yeni gelen talepleri hızlı aç.</div>
                                </div>
                            </div>
                            <div class="kt-card-content p-5">
                                @if($recentMessages->isNotEmpty())
                                    <div class="grid gap-3">
                                        @foreach($recentMessages as $message)
                                            <a href="{{ $message['url'] }}" class="dashboard-list-item">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="{{ $message['priority_badge'] }}">{{ $message['priority_label'] }}</span>
                                                        <span class="{{ $message['status_badge'] }}">{{ $message['status_label'] }}</span>
                                                    </div>
                                                    <div class="mt-2 truncate font-medium text-foreground">{{ $message['subject'] }}</div>
                                                    <div class="mt-1 text-sm text-muted-foreground">
                                                        {{ $message['sender'] }} · {{ $message['time'] }}
                                                    </div>
                                                </div>
                                                <i class="ki-filled ki-right text-muted-foreground"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="dashboard-empty-state">
                                        Son mesaj kutusu şu an boş görünüyor.
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endif

                    @if($dashboardSectionVisibility['upcoming_appointments'] ?? false)
                        <section class="kt-card">
                            <div class="kt-card-header py-5 flex-wrap gap-4">
                                <div>
                                    <h3 class="kt-card-title">{{ $canAppointments ? 'Yaklaşan randevular' : 'Operasyon notu' }}</h3>
                                    <div class="text-sm text-muted-foreground">
                                        {{ $canAppointments ? 'Takvimde yaklaşan görüşmeleri kaçırma.' : 'Randevu modülü için erişim yetkisi gereklidir.' }}
                                    </div>
                                </div>
                            </div>
                            <div class="kt-card-content p-5">
                                @if($canAppointments && $upcomingAppointments->isNotEmpty())
                                    <div class="grid gap-3">
                                        @foreach($upcomingAppointments as $appointment)
                                            <a href="{{ $appointment['url'] }}" class="dashboard-list-item">
                                                <div class="min-w-0">
                                                    <div class="font-medium text-foreground">{{ $appointment['title'] }}</div>
                                                    <div class="mt-1 text-sm text-muted-foreground">
                                                        {{ $appointment['time'] }}
                                                        @if($appointment['provider'])
                                                            · {{ $appointment['provider'] }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <i class="ki-filled ki-right text-muted-foreground"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                @elseif($canAppointments)
                                    <div class="dashboard-empty-state">
                                        Önümüzdeki günler için planlanmış randevu görünmüyor.
                                    </div>
                                @else
                                    <div class="dashboard-empty-state">
                                        Bu bölüm sadece randevu görüntüleme yetkisi olan kullanıcılar için aktif olur.
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endif

                    @if($dashboardSectionVisibility['recent_content'] ?? false)
                        <section class="kt-card">
                            <div class="kt-card-header py-5 flex-wrap gap-4">
                                <div>
                                    <h3 class="kt-card-title">Son güncellenen içerikler</h3>
                                    <div class="text-sm text-muted-foreground">Yarım kalan işi düzenleme ekranından devam ettir.</div>
                                </div>
                            </div>
                            <div class="kt-card-content p-5">
                                @if($recentContent->isNotEmpty())
                                    <div class="grid gap-3">
                                        @foreach($recentContent as $item)
                                            <a href="{{ $item['url'] }}" class="dashboard-list-item">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $item['type'] }}</span>
                                                        <span class="{{ $item['badge'] }}">{{ $item['meta'] }}</span>
                                                    </div>
                                                    <div class="mt-2 truncate font-medium text-foreground">{{ $item['title'] }}</div>
                                                    <div class="mt-1 text-sm text-muted-foreground">{{ $item['updated_label'] }}</div>
                                                </div>
                                                <i class="ki-filled ki-right text-muted-foreground"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="dashboard-empty-state">
                                        Görüntülenebilecek yeni bir içerik kaydı bulunmadı.
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endif
                </div>
            @endif

            @if(($dashboardSectionVisibility['audit_issues'] ?? false) && $canAudit)
                <section class="kt-card">
                    <div class="kt-card-header py-5 flex-wrap gap-4">
                        <div>
                            <h3 class="kt-card-title">Son sistem uyarıları</h3>
                            <div class="text-sm text-muted-foreground">
                                4xx ve 5xx kayıtlarını hızlı inceleyip ilgili sayfaya geç.
                            </div>
                        </div>
                        <a href="{{ route('admin.audit-logs.index') }}" class="kt-btn kt-btn-sm kt-btn-light">
                            Tüm loglar
                        </a>
                    </div>
                    <div class="kt-card-content p-0">
                        @if($recentAuditIssues->isNotEmpty())
                            <div class="kt-scrollable-x-auto overflow-y-hidden">
                                <table class="kt-table table-auto kt-table-border w-full">
                                    <thead>
                                    <tr>
                                        <th class="min-w-[120px]">Status</th>
                                        <th class="min-w-[120px]">Yöntem</th>
                                        <th class="min-w-[320px]">Route / URI</th>
                                        <th class="min-w-[150px]">Zaman</th>
                                        <th class="w-[90px] text-end">Detay</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($recentAuditIssues as $issue)
                                        <tr>
                                            <td>
                                                <span class="kt-badge kt-badge-sm {{ $issue['status'] >= 500 ? 'kt-badge-danger' : 'kt-badge-warning' }}">
                                                    {{ $issue['status'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="kt-badge kt-badge-sm kt-badge-light">{{ $issue['method'] ?: '-' }}</span>
                                            </td>
                                            <td class="font-medium text-foreground">{{ $issue['route'] }}</td>
                                            <td class="text-muted-foreground">{{ $issue['time'] }}</td>
                                            <td class="text-end">
                                                <a href="{{ $issue['url'] }}" class="kt-btn kt-btn-sm kt-btn-light-primary">Aç</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-5">
                                <div class="dashboard-empty-state">
                                    Son sistem akışı içinde kritik bir hata kaydı görünmüyor.
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

        </div>
    </div>
@endsection
