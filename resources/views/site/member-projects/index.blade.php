@extends('site.layouts.main.app')

@section('content')
    <div class="site-page">
        @include('site.partials.member-nav')

        <section class="mt-7 site-page-hero" data-reveal>
            <span class="site-eyebrow">Analiz Sürecim</span>
            <h1 class="site-title">Dosyadan sonuca tüm süreç tek yerde.</h1>
            <p class="site-lead">Dosyalarınızın alındığı andan raporlamaya kadar analizinizin hangi aşamada olduğunu takip edin.</p>
        </section>

        <form action="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}" method="GET" class="site-filter-panel sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
            <div class="site-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Proje adı veya içerik ara..." aria-label="Projelerimde ara">
            </div>
            <button type="submit" class="kt-btn kt-btn-primary min-h-12 rounded-full px-6">Ara</button>
        </form>

        <section class="mt-10 grid gap-4">
            <div class="site-section-heading mb-2">
                <div>
                    <span class="site-eyebrow">Çalışmalarım</span>
                    <h2 class="mt-4 site-section-title">{{ number_format($projects->total()) }} analiz çalışması</h2>
                </div>
                <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale, 'open' => 1]) }}" class="kt-btn kt-btn-light">Yeni randevu oluştur</a>
            </div>

            @forelse($projects as $project)
                <article class="site-card !overflow-visible !p-5 md:!p-6" data-reveal>
                    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="{{ \App\Models\Admin\Project\Project::statusBadgeClass($project->status) }}">{{ \App\Models\Admin\Project\Project::statusLabel($project->status) }}</span>
                                <span class="text-xs text-muted-foreground">#{{ $project->id }}</span>
                            </div>
                            <h2 class="mt-4 site-card-title !text-2xl">{{ $project->localizedValue('title') }}</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-muted-foreground">{{ $project->excerptPreview(170) }}</p>
                            <div class="mt-3 site-card-meta">
                                @if($project->appointment?->provider)<span>{{ $project->appointment->provider->name }}</span>@endif
                                <span>{{ $project->files_count }} dosya</span>
                                <span>Son işlem {{ $project->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <a href="{{ route('member.projects.show', ['project' => $project, 'site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary shrink-0">Süreci aç</a>
                    </div>
                </article>
            @empty
                <div class="site-empty">
                    <div>
                        <span class="site-eyebrow mx-auto">İlk adım</span>
                        <h2 class="mt-5 site-section-title">Henüz bir analiz çalışmanız bulunmuyor.</h2>
                        <p class="mx-auto mt-3 max-w-lg text-sm leading-7 text-muted-foreground">Ön görüşmeniz tamamlandığında analiz çalışma alanınız otomatik olarak oluşturulur.</p>
                        <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale, 'open' => 1]) }}" class="kt-btn kt-btn-primary mt-6">Randevu oluştur</a>
                    </div>
                </div>
            @endforelse
        </section>

        <section id="belgelerim" class="mt-10 rounded-3xl border border-border bg-background p-6 md:p-8" data-reveal>
            <span class="site-eyebrow">Belgelerim</span>
            <h2 class="mt-4 site-section-title">Dosyalarınız çalışma bazında korunur.</h2>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-muted-foreground">Bir çalışma alanını açarak veri dosyalarınızı yükleyebilir, daha önce paylaşılan belgeleri görüntüleyebilir ve izin verilen aşamalarda güncelleyebilirsiniz.</p>
        </section>

        <section id="sonuclarim" class="mt-7 rounded-3xl border border-border bg-background p-6 md:p-8" data-reveal>
            <span class="site-eyebrow">Sonuçlarım</span>
            <h2 class="mt-4 site-section-title">Rapor ve sonuç dosyalarınıza aynı çalışma alanından ulaşın.</h2>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-muted-foreground">Raporlama aşamasına ulaşan çalışmalarınızın paylaşılan çıktılarını ilgili analiz sürecini açarak indirebilirsiniz.</p>
        </section>

        <div class="mt-7">{{ $projects->links() }}</div>
    </div>
@endsection
