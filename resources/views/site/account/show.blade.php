@extends('site.layouts.main.app')

@section('content')
    <div class="site-page">
        @include('site.partials.member-nav')

        <section class="mt-7 site-page-hero" data-reveal>
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <span class="site-eyebrow">Üye çalışma alanı</span>
                    <h1 class="site-title mt-5">Merhaba, {{ $member->name }}.</h1>
                    <p class="site-lead mt-5">Randevularınızdan proje dosyalarınıza kadar tüm hizmet sürecinizi tek bir yerden yönetin.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <span class="{{ $member->statusBadgeClass() }} self-center">{{ $member->statusLabel() }}</span>
                    <a href="{{ route('member.account.edit', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary">Profili düzenle</a>
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="mt-6 rounded-2xl border border-success/25 bg-success/10 px-4 py-4 text-sm text-success">{{ session('success') }}</div>
        @endif

        <div class="mt-7 site-stat-grid" data-reveal>
            <div class="site-stat"><span class="text-sm text-muted-foreground">Toplam randevu</span><strong>{{ $member->appointments_count }}</strong></div>
            <div class="site-stat"><span class="text-sm text-muted-foreground">Aktif randevu</span><strong class="!text-primary">{{ $member->active_appointments_count }}</strong></div>
            <div class="site-stat"><span class="text-sm text-muted-foreground">Projeler</span><strong>{{ $member->projects_count }}</strong></div>
            <div class="site-stat"><span class="text-sm text-muted-foreground">Bekleyen değerlendirme</span><strong class="!text-warning">{{ $member->pending_service_reviews_count }}</strong></div>
        </div>

        @if($pendingReviews->isNotEmpty())
            <section class="mt-7 rounded-3xl border border-warning/30 bg-warning/10 p-6" data-reveal>
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <span class="site-eyebrow !text-warning">Görüşünüzü bekliyoruz</span>
                        <h2 class="mt-4 site-section-title !text-2xl">{{ $pendingReviews->count() }} hizmet değerlendirilmeyi bekliyor.</h2>
                        <p class="mt-2 text-sm leading-7 text-muted-foreground">Kısa anketle deneyiminizi paylaşarak hizmet kalitesini geliştirmemize yardımcı olabilirsiniz.</p>
                    </div>
                    <a href="{{ route('member.reviews.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary shrink-0">Değerlendirmeleri aç</a>
                </div>
            </section>
        @endif

        <div class="mt-8 grid gap-7 lg:grid-cols-[minmax(0,1.2fr)_minmax(20rem,.8fr)]">
            <section class="grid content-start gap-7">
                <div class="rounded-3xl border border-border bg-background p-6 md:p-8">
                    <div class="site-section-heading mb-6">
                        <div>
                            <span class="site-eyebrow">Son hareketler</span>
                            <h2 class="mt-4 site-section-title">Projelerim</h2>
                        </div>
                        <a href="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}" class="text-sm font-semibold text-primary">Tümünü gör</a>
                    </div>

                    <div class="grid gap-3">
                        @forelse($latestProjects as $project)
                            <a href="{{ route('member.projects.show', ['project' => $project, 'site_locale' => $siteCurrentLocale]) }}" class="group rounded-2xl border border-border p-4 transition hover:border-primary/40 hover:bg-primary/5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <h3 class="truncate font-semibold text-foreground group-hover:text-primary">{{ $project->localizedValue('title') }}</h3>
                                        <div class="mt-2 flex flex-wrap gap-3 text-xs text-muted-foreground">
                                            <span>{{ \App\Models\Admin\Project\Project::statusLabel($project->status) }}</span>
                                            <span>{{ $project->files_count }} dosya</span>
                                            <span>{{ $project->updated_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    <span class="text-primary">→</span>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-2xl border border-dashed border-border px-5 py-10 text-center">
                                <p class="text-sm text-muted-foreground">Tamamlanan bir randevudan sonra proje alanınız burada açılacak.</p>
                                <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary mt-5">Randevu oluştur</a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-border bg-background p-6 md:p-8">
                    <div class="site-section-heading">
                        <div>
                            <span class="site-eyebrow">Profil özeti</span>
                            <h2 class="mt-4 site-section-title">İletişim bilgilerim</h2>
                        </div>
                        <a href="{{ route('member.account.edit', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">Düzenle</a>
                    </div>
                    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-border bg-muted/30 p-4"><dt class="text-xs uppercase tracking-[0.14em] text-muted-foreground">Ad soyad</dt><dd class="mt-2 text-sm font-semibold text-foreground">{{ $member->full_name }}</dd></div>
                        <div class="rounded-2xl border border-border bg-muted/30 p-4"><dt class="text-xs uppercase tracking-[0.14em] text-muted-foreground">E-posta</dt><dd class="mt-2 break-all text-sm font-semibold text-foreground">{{ $member->email }}</dd></div>
                        <div class="rounded-2xl border border-border bg-muted/30 p-4"><dt class="text-xs uppercase tracking-[0.14em] text-muted-foreground">Telefon</dt><dd class="mt-2 text-sm font-semibold text-foreground">{{ $member->phone ?: 'Henüz eklenmedi' }}</dd></div>
                        <div class="rounded-2xl border border-border bg-muted/30 p-4"><dt class="text-xs uppercase tracking-[0.14em] text-muted-foreground">Son giriş</dt><dd class="mt-2 text-sm font-semibold text-foreground">{{ optional($member->last_login_at)->format('d.m.Y H:i') ?: 'İlk oturum' }}</dd></div>
                    </dl>
                </div>
            </section>

            <aside class="grid content-start gap-7">
                <div class="rounded-3xl border border-border bg-background p-6">
                    <span class="site-eyebrow">Hızlı işlemler</span>
                    <div class="mt-6 grid gap-3">
                        <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary w-full">Randevu oluştur</a>
                        <a href="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full">Projelerime git</a>
                        <a href="{{ route('member.reviews.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full">Değerlendirmelerim</a>
                        <a href="{{ route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full">Mesaj gönder</a>
                    </div>
                </div>

                <div class="rounded-3xl border border-border bg-background p-6">
                    <span class="site-eyebrow">Üyelik bilgisi</span>
                    <div class="mt-5 grid gap-4 text-sm">
                        <div><span class="text-muted-foreground">Koşullar</span><div class="mt-1 font-semibold text-foreground">{{ $member->hasAcceptedMembershipTerms() ? 'Kabul edildi' : 'Onay bekliyor' }}</div></div>
                        <div><span class="text-muted-foreground">Onay tarihi</span><div class="mt-1 font-semibold text-foreground">{{ optional($member->membership_terms_accepted_at)->format('d.m.Y H:i') ?: '-' }}</div></div>
                        <a href="{{ route('member.terms.show', ['site_locale' => $siteCurrentLocale]) }}" class="text-sm font-semibold text-primary">Bilgilendirme metnini aç →</a>
                    </div>
                </div>

                <div class="rounded-3xl border border-danger/20 bg-background p-6">
                    <span class="site-eyebrow !text-danger">Hesap güvenliği</span>
                    <h2 class="mt-4 text-lg font-semibold text-foreground">Üyeliği sonlandır</h2>
                    <p class="mt-3 text-sm leading-7 text-muted-foreground">Hesabınız pasife alınır; geçmiş operasyon kayıtları yasal ve hizmet bütünlüğü gerekleriyle korunur.</p>

                    @if($errors->has('termination'))
                        <div class="mt-4 rounded-2xl border border-danger/25 bg-danger/10 px-4 py-4 text-sm text-danger">{{ $errors->first('termination') }}</div>
                    @endif

                    @if($hasUpcomingAppointment)
                        <div class="mt-5 rounded-2xl border border-warning/25 bg-warning/10 px-4 py-4 text-sm text-warning">Yaklaşan randevunuz varken üyelik sonlandırılamaz.</div>
                    @else
                        <form method="POST" action="{{ route('member.account.terminate', ['site_locale' => $siteCurrentLocale]) }}" class="mt-5 grid gap-4" data-member-termination-form>
                            @csrf
                            <div class="grid gap-2">
                                <label for="member_terminate_current_password" class="kt-form-label">Mevcut parola</label>
                                <input id="member_terminate_current_password" type="password" name="current_password" class="kt-input @error('current_password') kt-input-invalid @enderror" autocomplete="current-password" required>
                                @error('current_password')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                            </div>
                            <label class="flex items-start gap-3 rounded-2xl border border-border bg-muted/30 p-4 text-sm leading-6 text-muted-foreground">
                                <input type="checkbox" name="confirm_termination" value="1" class="kt-checkbox mt-1" required>
                                <span>Bu işlemden sonra hesabımla giriş yapamayacağımı biliyorum.</span>
                            </label>
                            <button type="submit" class="kt-btn kt-btn-danger w-full">Üyeliğimi sonlandır</button>
                        </form>
                    @endif
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('site_js')
    @vite('resources/js/site/member-portal.js')
@endpush
