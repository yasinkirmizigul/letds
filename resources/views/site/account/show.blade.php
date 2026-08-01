@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_420px]">
            <section class="grid gap-6">
                <div class="rounded-3xl border border-border bg-background p-8">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">
                                Üyelik Hesabım
                            </div>
                            <h1 class="mt-5 font-display text-3xl font-semibold leading-tight text-foreground md:text-4xl">{{ $member->full_name }}</h1>
                            <p class="mt-3 text-sm leading-7 text-muted-foreground">
                                Hesap durumunuzu, üyelik onay bilginizi ve güvenlik işlemlerinizi buradan yönetebilirsiniz.
                            </p>
                        </div>
                        <span class="{{ $member->statusBadgeClass() }}">{{ $member->statusLabel() }}</span>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-3xl border border-border bg-background p-5">
                        <div class="text-sm text-muted-foreground">Toplam Randevu</div>
                        <div class="mt-2 text-3xl font-semibold">{{ $member->appointments_count }}</div>
                    </div>
                    <div class="rounded-3xl border border-border bg-background p-5">
                        <div class="text-sm text-muted-foreground">Aktif Randevu</div>
                        <div class="mt-2 text-3xl font-semibold text-primary">{{ $member->active_appointments_count }}</div>
                    </div>
                    <div class="rounded-3xl border border-border bg-background p-5">
                        <div class="text-sm text-muted-foreground">Mesaj Kaydı</div>
                        <div class="mt-2 text-3xl font-semibold text-success">{{ $member->contact_messages_count }}</div>
                    </div>
                    <div class="rounded-3xl border border-border bg-background p-5">
                        <div class="text-sm text-muted-foreground">Bekleyen Değerlendirme</div>
                        <div class="mt-2 text-3xl font-semibold text-warning">{{ $member->pending_service_reviews_count }}</div>
                    </div>
                    <div class="rounded-3xl border border-border bg-background p-5">
                        <div class="text-sm text-muted-foreground">Son Giriş</div>
                        <div class="mt-2 text-lg font-semibold">{{ optional($member->last_login_at)->format('d.m.Y H:i') ?: 'Henüz giriş yok' }}</div>
                    </div>
                </div>

                @if($pendingReviews->isNotEmpty())
                    <section class="border border-warning/30 bg-warning/10 p-6">
                        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="text-xs font-semibold uppercase text-warning">Görüşünüzü Bekliyoruz</div>
                                <h2 class="mt-3 text-lg font-semibold text-foreground">{{ $pendingReviews->count() }} hizmet değerlendirilmeyi bekliyor</h2>
                                <p class="mt-2 text-sm leading-7 text-muted-foreground">Kısa anketi yanıtlayarak aldığınız hizmeti 1–5 yıldız arasında puanlayabilirsiniz.</p>
                            </div>
                            <a href="{{ route('member.reviews.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary shrink-0">Değerlendirmeleri Aç</a>
                        </div>
                    </section>
                @endif

                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="rounded-3xl border border-border bg-background p-6">
                        <h2 class="font-display text-lg font-semibold text-foreground">Profil Özeti</h2>
                        <div class="mt-5 grid gap-4 text-sm text-muted-foreground">
                            <div class="rounded-2xl border border-border bg-muted/20 px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">E-posta</div>
                                <div class="mt-2 font-medium text-foreground">{{ $member->email }}</div>
                            </div>
                            <div class="rounded-2xl border border-border bg-muted/20 px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Telefon</div>
                                <div class="mt-2 font-medium text-foreground">{{ $member->phone ?: 'Telefon bilgisi girilmemiş' }}</div>
                            </div>
                            <div class="rounded-2xl border border-border bg-muted/20 px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Belge Durumu</div>
                                <div class="mt-2 font-medium text-foreground">{{ $member->hasDocument() ? ($member->documentName() ?: 'Belge yüklendi') : 'Belge yüklenmedi' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-border bg-background p-6">
                        <h2 class="font-display text-lg font-semibold text-foreground">Üyelik Onayı</h2>
                        <div class="mt-5 grid gap-4 text-sm text-muted-foreground">
                            <div class="rounded-2xl border border-border bg-muted/20 px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Onay Durumu</div>
                                <div class="mt-2 font-medium text-foreground">
                                    {{ $member->hasAcceptedMembershipTerms() ? 'Bilgilendirme metni kabul edildi' : 'Onay bilgisi bulunmuyor' }}
                                </div>
                            </div>
                            <div class="rounded-2xl border border-border bg-muted/20 px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Onay Tarihi</div>
                                <div class="mt-2 font-medium text-foreground">
                                    {{ optional($member->membership_terms_accepted_at)->format('d.m.Y H:i') ?: 'Kayıt bulunamadı' }}
                                </div>
                            </div>
                            <div class="rounded-2xl border border-border bg-muted/20 px-4 py-4">
                                <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Metin Versiyonu</div>
                                <div class="mt-2 font-medium text-foreground">{{ $member->membership_terms_version ?: config('membership_terms.version') }}</div>
                            </div>
                            <a href="{{ route('member.terms.show', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full">Bilgilendirme Metnini Aç</a>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="grid gap-6 self-start lg:sticky lg:top-24">
                @if(session('success'))
                    <div class="rounded-2xl border border-success/25 bg-success/10 px-4 py-4 text-sm text-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->has('termination'))
                    <div class="rounded-2xl border border-danger/25 bg-danger/10 px-4 py-4 text-sm text-danger">
                        {{ $errors->first('termination') }}
                    </div>
                @endif

                <div class="rounded-3xl border border-border bg-background p-6">
                    <h2 class="font-display text-lg font-semibold text-foreground">Hesap Güvenliği</h2>
                    <div class="mt-4 grid gap-3 text-sm text-muted-foreground">
                        <a href="{{ route('member.password.request', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full">Şifremi Yenile</a>
                        <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full">Randevu Paneline Git</a>
                        <a href="{{ route('member.reviews.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full">Değerlendirmelerim</a>
                    </div>
                </div>

                <div class="rounded-3xl border border-danger/20 bg-background p-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-danger">
                        Üyeliği Sonlandır
                    </div>
                    <h2 class="mt-4 font-display text-lg font-semibold text-foreground">Hesabı pasife al</h2>
                    <p class="mt-3 text-sm leading-7 text-muted-foreground">
                        Bu işlem hesabınızı silmez; yalnızca pasife alır. Geçmiş kayıtlar, operasyonel bütünlük ve yasal yükümlülükler için sistemde saklanmaya devam eder.
                    </p>

                    @if($hasUpcomingAppointment)
                        <div class="mt-5 rounded-2xl border border-warning/25 bg-warning/10 px-4 py-4 text-sm text-warning">
                            Yaklaşan randevunuz bulunduğu için üyeliğinizi şu anda sonlandıramazsınız. Önce randevu sürecinizi kapatın.
                        </div>
                    @else
                        <form method="POST" action="{{ route('member.account.terminate', ['site_locale' => $siteCurrentLocale]) }}" class="mt-5 grid gap-4">
                            @csrf

                            <div class="grid gap-2">
                                <label class="text-sm font-medium text-foreground" for="member_terminate_current_password">Mevcut Şifre</label>
                                <input
                                    id="member_terminate_current_password"
                                    type="password"
                                    name="current_password"
                                    class="kt-input @error('current_password') kt-input-invalid @enderror"
                                    placeholder="Şifrenizi doğrulayın"
                                    autocomplete="current-password"
                                    required
                                >
                                @error('current_password')
                                    <div class="mt-1.5 text-xs text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <label class="flex items-start gap-3 rounded-2xl border border-border bg-muted/20 px-4 py-4 text-sm text-muted-foreground">
                                <input type="checkbox" name="confirm_termination" value="1" class="kt-checkbox mt-1" required>
                                <span>Üyeliğimin pasife alınacağını ve bu işlem sonrası hesabımla giriş yapamayacağımı biliyorum.</span>
                            </label>

                            <button type="submit" class="kt-btn kt-btn-danger w-full" onclick="return confirm('Üyeliğiniz pasife alınacak. Devam etmek istiyor musunuz?')">
                                Üyeliğimi Sonlandır
                            </button>
                        </form>
                    @endif
                </div>
            </aside>
        </div>
    </div>
@endsection
