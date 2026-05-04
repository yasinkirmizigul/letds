@extends('admin.layouts.main.app')

@section('content')
    @php
        $showHeroChips = $dashboardSectionVisibility['hero_chips'] ?? false;
        $showHeroQuickActions = $dashboardSectionVisibility['hero_quick_actions'] ?? false;
        $showHeroFocusList = $dashboardSectionVisibility['hero_focus_list'] ?? false;
        $visibleKpis = collect($kpis)->filter(fn ($kpi) => $dashboardSectionVisibility[$kpi['visibility_key']] ?? true)->values();
        $visibleModuleCards = collect($moduleCards)->filter(fn ($card) => $dashboardSectionVisibility[$card['visibility_key']] ?? true)->values();
        $showMonthlyChart = $dashboardSectionVisibility['chart_monthly_activity'] ?? false;
        $showActionChart = $dashboardSectionVisibility['chart_action_breakdown'] ?? false;
        $showScheduleChart = ($dashboardSectionVisibility['chart_schedule_flow'] ?? false) && $canAppointments;
        $hasRenderableDashboardSection =
            ($dashboardSectionVisibility['hero_overview'] ?? false)
            || (($dashboardSectionVisibility['kpi_overview'] ?? false) && $visibleKpis->isNotEmpty())
            || (($dashboardSectionVisibility['module_overview'] ?? false) && $visibleModuleCards->isNotEmpty())
            || (($dashboardSectionVisibility['activity_charts'] ?? false) && ($showMonthlyChart || $showActionChart || $showScheduleChart))
            || ($dashboardSectionVisibility['recent_messages'] ?? false)
            || ($dashboardSectionVisibility['upcoming_appointments'] ?? false)
            || ($dashboardSectionVisibility['recent_content'] ?? false)
            || (($dashboardSectionVisibility['audit_issues'] ?? false) && $canAudit);
    @endphp

    <div class="kt-container-fixed max-w-[90%] dashboard-shell"
         data-page="dash.index"
         data-monthly-chart='@json($monthlyActivity)'
         data-action-chart='@json($actionChart)'
         data-schedule-chart='@json($scheduleChart)'>

        <div class="grid gap-5 lg:gap-7.5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Kontrol Paneli</h1>
                    <div class="text-sm text-muted-foreground">
                        Panelde hangi blokların görüneceğini buradan yönetebilirsin.
                    </div>
                </div>

                <a href="{{ route('admin.dashboard.manage') }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-setting-2"></i>
                    Kontrol paneli yönetimi
                </a>
            </div>

            @unless($hasRenderableDashboardSection)
                <section class="kt-card overflow-hidden">
                    <div class="kt-card-content px-6 py-10 text-center">
                        <div class="mx-auto inline-flex size-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <i class="ki-filled ki-setting-2 text-2xl"></i>
                        </div>
                        <h2 class="mt-5 text-lg font-semibold text-foreground">Tüm kontrol paneli blokları gizlenmiş durumda</h2>
                        <p class="mx-auto mt-3 max-w-[60ch] text-sm leading-6 text-muted-foreground">
                            Kontrol paneli yönetimi ekranından blokları tekrar açabilir veya varsayılan düzene geri dönebilirsin.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('admin.dashboard.manage') }}" class="kt-btn kt-btn-primary">
                                Kontrol paneli bloklarını yönet
                            </a>
                        </div>
                    </div>
                </section>
            @endunless

            @if($dashboardSectionVisibility['hero_overview'] ?? false)
                <section class="dashboard-hero kt-card" style="order: {{ $dashboardSectionOrderIndex['hero_overview'] ?? 20 }};">
                    <div class="dashboard-hero__orb dashboard-hero__orb--primary"></div>
                    <div class="dashboard-hero__orb dashboard-hero__orb--secondary"></div>

                    <div class="kt-card-content p-6 lg:p-8">
                        <div class="grid gap-6 {{ $showHeroFocusList ? 'xl:grid-cols-[1.25fr,.75fr]' : '' }} xl:items-start">
                            <div class="relative z-[1]">
                                <div class="dashboard-kicker">Yönetim merkezi</div>
                                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-foreground lg:text-3xl">
                                    {{ $greeting }}, operasyon nabzı burada.
                                </h2>
                                <p class="mt-3 max-w-[68ch] text-sm leading-6 text-muted-foreground lg:text-base">
                                    {{ $heroSummary }}
                                </p>

                                @if($showHeroChips)
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
                                            {{ $visibleModuleCards->count() }} hızlı modül
                                        </span>
                                    </div>
                                @endif

                                @if($showHeroQuickActions && count($quickActions))
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

                            @if($showHeroFocusList)
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
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            @if(($dashboardSectionVisibility['kpi_overview'] ?? false) && $visibleKpis->isNotEmpty())
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5" style="order: {{ $dashboardSectionOrderIndex['kpi_overview'] ?? 30 }};">
                    @foreach($visibleKpis as $kpi)
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

            @if(($dashboardSectionVisibility['module_overview'] ?? false) && $visibleModuleCards->isNotEmpty())
                <section class="kt-card" style="order: {{ $dashboardSectionOrderIndex['module_overview'] ?? 40 }};">
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
                            @foreach($visibleModuleCards as $card)
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

            @if(($dashboardSectionVisibility['activity_charts'] ?? false) && ($showMonthlyChart || $showActionChart || $showScheduleChart))
                <div class="grid gap-5 xl:grid-cols-[1.35fr,.65fr]" style="order: {{ $dashboardSectionOrderIndex['activity_charts'] ?? 50 }};">
                    @if($showMonthlyChart)
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
                    @endif

                    <div class="grid gap-5">
                        @if($showActionChart)
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
                        @endif

                        @if($showScheduleChart)
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
                <div class="grid gap-5 xl:grid-cols-3" style="order: {{ $dashboardFlowOrder }}">
                    @if($dashboardSectionVisibility['recent_messages'] ?? false)
                        <section class="kt-card" style="order: {{ $dashboardSectionOrderIndex['recent_messages'] ?? 60 }};">
                            <div class="kt-card-header py-5 flex-wrap gap-4">
                                <div>
                                    <h3 class="kt-card-title">Son mesajlar</h3>
                                    <div class="text-sm text-muted-foreground">Yeni gelen talepleri hızlı aç.</div>
                                </div>
                            </div>
                            <div class="kt-card-content p-5">
                                @if($recentMessages->isNotEmpty())
                                    <div
                                        id="dashboardRecentMessagesTimeline"
                                        data-history-timeline
                                        data-history-timeline-compact="true"
                                        data-history-timeline-height="280px"
                                        data-history-timeline-empty="Son mesaj kutusu şu an boş görünüyor."
                                        data-history-timeline-source="#dashboardRecentMessagesTimelineData"
                                    ></div>
                                    <script type="application/json" id="dashboardRecentMessagesTimelineData">@json($recentMessages)</script>
                                @else
                                    <div class="dashboard-empty-state">
                                        Son mesaj kutusu şu an boş görünüyor.
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endif

                    @if($dashboardSectionVisibility['upcoming_appointments'] ?? false)
                        <section class="kt-card" style="order: {{ $dashboardSectionOrderIndex['upcoming_appointments'] ?? 70 }};">
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
                                    <div
                                        id="dashboardUpcomingAppointmentsTimeline"
                                        data-history-timeline
                                        data-history-timeline-compact="true"
                                        data-history-timeline-height="280px"
                                        data-history-timeline-empty="Önümüzdeki günler için planlanmış randevu görünmüyor."
                                        data-history-timeline-source="#dashboardUpcomingAppointmentsTimelineData"
                                    ></div>
                                    <script type="application/json" id="dashboardUpcomingAppointmentsTimelineData">@json($upcomingAppointments)</script>
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
                        <section class="kt-card" style="order: {{ $dashboardSectionOrderIndex['recent_content'] ?? 80 }};">
                            <div class="kt-card-header py-5 flex-wrap gap-4">
                                <div>
                                    <h3 class="kt-card-title">Son güncellenen içerikler</h3>
                                    <div class="text-sm text-muted-foreground">Yarım kalan işi düzenleme ekranından devam ettir.</div>
                                </div>
                            </div>
                            <div class="kt-card-content p-5">
                                @if($recentContent->isNotEmpty())
                                    <div
                                        id="dashboardRecentContentTimeline"
                                        data-history-timeline
                                        data-history-timeline-compact="true"
                                        data-history-timeline-height="280px"
                                        data-history-timeline-empty="Görüntülenebilecek yeni bir içerik kaydı bulunmadı."
                                        data-history-timeline-source="#dashboardRecentContentTimelineData"
                                    ></div>
                                    <script type="application/json" id="dashboardRecentContentTimelineData">@json($recentContent)</script>
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
                <section class="kt-card" style="order: {{ $dashboardSectionOrderIndex['audit_issues'] ?? 90 }};">
                    <div class="kt-card-header py-5 flex-wrap gap-4">
                        <div>
                            <h3 class="kt-card-title">Son sistem uyarıları</h3>
                            <div class="text-sm text-muted-foreground">
                                4xx ve 5xx kayıtlarını hızlı inceleyip ilgili sayfaya geç.
                            </div>
                        </div>
                        <a href="{{ route('admin.audit-logs.index') }}" class="kt-btn kt-btn-sm kt-btn-light">
                            Tüm sistem kayıtları
                        </a>
                    </div>
                    <div class="kt-card-content p-0">
                        @if($recentAuditIssues->isNotEmpty())
                            <div class="p-5">
                                <div
                                    id="dashboardAuditIssuesTimeline"
                                    data-history-timeline
                                    data-history-timeline-compact="true"
                                    data-history-timeline-height="280px"
                                    data-history-timeline-empty="Son sistem akışı içinde kritik bir hata kaydı görünmüyor."
                                    data-history-timeline-source="#dashboardAuditIssuesTimelineData"
                                ></div>
                                <script type="application/json" id="dashboardAuditIssuesTimelineData">@json($recentAuditIssues)</script>
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
