@extends('site.layouts.main.app')

@php
    $membershipTermsTitle = $siteSettings->localized('member_terms_title') ?: config('membership_terms.title');
    $membershipTermsSummary = $siteSettings->localized('member_terms_summary') ?: config('membership_terms.summary');
    $membershipTermsContent = $siteSettings->localized('member_terms_content') ?: config('membership_terms.content');
    $hasReadTerms = old('membership_terms_read') === '1' || old('membership_terms_read') === 1;
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_560px]">
            <section class="overflow-hidden rounded-[36px] border border-border bg-white/90 shadow-sm">
                <div class="grid gap-8 p-8 lg:p-10">
                    <div>
                        <div class="inline-flex rounded-full border border-primary/20 bg-primary/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-primary">
                            Üye Kaydı
                        </div>
                        <h1 class="mt-5 text-4xl font-semibold leading-tight text-foreground">Sitenize güçlü bir üyelik başlangıcı kazandırın.</h1>
                        <p class="mt-5 max-w-3xl text-sm leading-8 text-muted-foreground">
                            Bu form ile üyeleriniz temel bilgilerini, iletişim bilgilerini ve gerekli dosyalarını iletebilir. Kayıt tamamlandığında hesap oluşturulur ve yönetim panelinden tüm süreçleri takip edebilirsiniz.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-[28px] border border-border bg-muted/20 p-5">
                            <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">1. Adım</div>
                            <div class="mt-3 text-lg font-semibold text-foreground">Bilgileri Gir</div>
                            <div class="mt-2 text-sm leading-7 text-muted-foreground">Ad, soyad, e-posta ve telefon bilgilerinizle hesabınızı oluşturun.</div>
                        </div>
                        <div class="rounded-[28px] border border-border bg-muted/20 p-5">
                            <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">2. Adım</div>
                            <div class="mt-3 text-lg font-semibold text-foreground">Bilgi Metnini Oku</div>
                            <div class="mt-2 text-sm leading-7 text-muted-foreground">Üyelik bilgilendirmesini okuyup onayladıktan sonra kayıt tamamlanır.</div>
                        </div>
                        <div class="rounded-[28px] border border-border bg-muted/20 p-5">
                            <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">3. Adım</div>
                            <div class="mt-3 text-lg font-semibold text-foreground">Panele Geç</div>
                            <div class="mt-2 text-sm leading-7 text-muted-foreground">Kaydınız tamamlandığında üye paneline yönlendirilirsiniz.</div>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-border bg-gradient-to-br from-primary/10 via-white to-white p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-primary">Üyelik Bilgilendirmesi</div>
                        <div class="mt-3 text-2xl font-semibold text-foreground">{{ $membershipTermsTitle }}</div>
                        <div class="mt-3 text-sm leading-7 text-muted-foreground">{{ $membershipTermsSummary }}</div>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <button type="button" class="kt-btn kt-btn-primary" data-membership-terms-open>Metni Oku</button>
                            <a href="{{ route('member.terms.show', ['site_locale' => $siteCurrentLocale]) }}" target="_blank" class="kt-btn kt-btn-light">Tam Sayfada Aç</a>
                        </div>
                        <div class="mt-4 text-xs text-muted-foreground" data-membership-terms-status>
                            Metni açıp sonuna kadar kaydırdıktan sonra kabul kutusu aktif olur.
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[36px] border border-border bg-slate-950 p-6 text-white shadow-[0_24px_80px_rgba(15,23,42,0.18)] lg:p-8">
                <div class="mb-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.28em] text-white/60">Kayıt Formu</div>
                    <h2 class="mt-2 text-3xl font-semibold">Yeni Üyelik Oluştur</h2>
                    <p class="mt-3 text-sm leading-7 text-white/70">
                        Tüm alanları eksiksiz doldurun. Belge alanı isteğe bağlıdır; yüklediğiniz dosya yönetim panelinden görüntülenebilir.
                    </p>
                </div>

                <form method="POST" action="{{ route('member.register.post') }}" enctype="multipart/form-data" class="grid gap-5">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium text-white" for="member_register_name">Ad</label>
                            <input
                                id="member_register_name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="kt-input @error('name') kt-input-invalid @enderror"
                                placeholder="Adınız"
                                required
                            >
                            @error('name')
                                <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium text-white" for="member_register_surname">Soyad</label>
                            <input
                                id="member_register_surname"
                                type="text"
                                name="surname"
                                value="{{ old('surname') }}"
                                class="kt-input @error('surname') kt-input-invalid @enderror"
                                placeholder="Soyadınız"
                                required
                            >
                            @error('surname')
                                <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-white" for="member_register_email">E-posta</label>
                        <input
                            id="member_register_email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="kt-input @error('email') kt-input-invalid @enderror"
                            placeholder="ornek@alanadi.com"
                            autocomplete="email"
                            required
                        >
                        @error('email')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-white" for="member_register_phone">Telefon</label>
                        <input
                            id="member_register_phone"
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            class="kt-input @error('phone') kt-input-invalid @enderror"
                            placeholder="+90 5xx xxx xx xx"
                        >
                        @error('phone')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium text-white" for="member_register_password">Şifre</label>
                            <input
                                id="member_register_password"
                                type="password"
                                name="password"
                                class="kt-input @error('password') kt-input-invalid @enderror"
                                placeholder="En az 8 karakter"
                                autocomplete="new-password"
                                required
                            >
                            @error('password')
                                <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium text-white" for="member_register_password_confirmation">Şifre Tekrar</label>
                            <input
                                id="member_register_password_confirmation"
                                type="password"
                                name="password_confirmation"
                                class="kt-input"
                                placeholder="Şifrenizi tekrar girin"
                                autocomplete="new-password"
                                required
                            >
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-white" for="member_register_filepath">Belge / Dosya</label>
                        <input
                            id="member_register_filepath"
                            type="file"
                            name="filepath"
                            class="kt-input @error('filepath') kt-input-invalid @enderror"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                        >
                        <div class="text-xs text-white/60">Desteklenen türler: PDF, JPG, PNG, WEBP, DOC, DOCX. En fazla 12 MB.</div>
                        @error('filepath')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <input type="hidden" name="membership_terms_read" value="{{ $hasReadTerms ? 1 : 0 }}" data-membership-terms-read-input>

                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-white">Üyelik bilgilendirmesi onayı</div>
                                <div class="mt-2 text-sm leading-7 text-white/70">
                                    Kayıt işleminden önce bilgilendirme metnini okuyup onaylamanız gerekir.
                                </div>
                            </div>
                            <button type="button" class="kt-btn kt-btn-light" data-membership-terms-open>Metni Aç</button>
                        </div>

                        <label class="mt-4 flex items-start gap-3 rounded-2xl border border-white/10 bg-black/10 px-4 py-4 text-sm text-white/80">
                            <input
                                type="checkbox"
                                name="membership_terms_accepted"
                                value="1"
                                class="kt-checkbox mt-1"
                                data-membership-terms-checkbox
                                @checked(old('membership_terms_accepted'))
                                @disabled(!$hasReadTerms)
                            >
                            <span>
                                Üyelik bilgilendirme metnini okudum ve kabul ediyorum.
                                <span class="mt-2 block text-xs text-white/60">Kutu, metin okunmadan aktif hale gelmez.</span>
                            </span>
                        </label>
                        @error('membership_terms_read')
                            <div class="mt-2 text-xs text-danger">{{ $message }}</div>
                        @enderror
                        @error('membership_terms_accepted')
                            <div class="mt-2 text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="kt-btn kt-btn-primary w-full">Üyeliği Oluştur</button>
                </form>

                <div class="mt-6 rounded-[28px] border border-white/10 bg-white/10 p-5 text-sm leading-7 text-white/70">
                    Zaten hesabınız varsa doğrudan giriş yapabilirsiniz.
                    <a href="{{ route('member.login', ['site_locale' => $siteCurrentLocale]) }}" class="ml-2 font-medium text-white underline underline-offset-4">Üye girişine git</a>
                </div>
            </section>
        </div>
    </div>

    <div class="fixed inset-0 z-[120] hidden bg-slate-950/60 px-4 py-6" data-membership-terms-modal>
        <div class="mx-auto flex h-full max-w-4xl flex-col overflow-hidden rounded-[32px] border border-border bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-border px-6 py-5">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-primary">Üyelik Bilgilendirmesi</div>
                    <h3 class="mt-2 text-2xl font-semibold text-foreground">{{ $membershipTermsTitle }}</h3>
                    <div class="mt-2 text-sm text-muted-foreground">{{ $membershipTermsSummary }}</div>
                </div>
                <button type="button" class="kt-btn kt-btn-light" data-membership-terms-close>Kapat</button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 text-sm leading-8 text-muted-foreground" data-membership-terms-scrollable>
                {!! nl2br(e($membershipTermsContent)) !!}
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-6 py-5">
                <div class="text-sm text-muted-foreground" data-membership-terms-modal-status>
                    Metnin sonuna ulaştığınızda kabul kutusu aktifleşir.
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('member.terms.show', ['site_locale' => $siteCurrentLocale]) }}" target="_blank" class="kt-btn kt-btn-light">Tam Sayfada Aç</a>
                    <button type="button" class="kt-btn kt-btn-primary" data-membership-terms-close>Okumaya Devam Et</button>
                </div>
            </div>
        </div>
    </div>
@endsection
