@extends('site.layouts.main.app')

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
                            <div class="mt-3 text-lg font-semibold text-foreground">Belgeni Yükle</div>
                            <div class="mt-2 text-sm leading-7 text-muted-foreground">Gerekliyse PDF, görsel veya ofis dokümanınızı güvenli şekilde ekleyin.</div>
                        </div>
                        <div class="rounded-[28px] border border-border bg-muted/20 p-5">
                            <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">3. Adım</div>
                            <div class="mt-3 text-lg font-semibold text-foreground">Panele Geç</div>
                            <div class="mt-2 text-sm leading-7 text-muted-foreground">Kaydınız tamamlandığında üye paneline yönlendirilirsiniz.</div>
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

                    <button type="submit" class="kt-btn kt-btn-primary w-full">Üyeliği Oluştur</button>
                </form>

                <div class="mt-6 rounded-[28px] border border-white/10 bg-white/10 p-5 text-sm leading-7 text-white/70">
                    Zaten hesabınız varsa doğrudan giriş yapabilirsiniz.
                    <a href="{{ route('member.login') }}" class="ml-2 font-medium text-white underline underline-offset-4">Üye girişine git</a>
                </div>
            </section>
        </div>
    </div>
@endsection
