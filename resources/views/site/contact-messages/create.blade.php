@extends('site.layouts.main.app')

@php
    $isMember = $member !== null;
    $selectedChannels = old('contact_channels', []);
    if (!is_array($selectedChannels)) {
        $selectedChannels = [$selectedChannels];
    }
@endphp

@section('content')
    <div
        class="mx-auto max-w-6xl py-6"
        id="contact-message-page"
        data-is-member="{{ $isMember ? 1 : 0 }}"
        data-success-message="{{ session('ok', '') }}"
    >
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.75fr)]">
            <div class="kt-card">
                <div class="kt-card-header border-b border-border/60 py-5">
                    <div class="flex flex-col gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-primary">İletişim Formu</span>
                        <div>
                            <h1 class="text-2xl font-semibold text-foreground">Doğru kişiye doğrudan mesaj gönder</h1>
                            <p class="text-sm text-muted-foreground">
                                Mesajın seçtiğin kullanıcıya admin panel üzerinden düşer. Aciliyet durumunu da belirterek daha net bir yönlendirme yapabilirsin.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="kt-card-content p-6">
                    @if(session('ok'))
                        <div class="kt-alert kt-alert-success mb-5">
                            <div class="kt-alert-text">{{ session('ok') }}</div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="kt-alert kt-alert-danger mb-5">
                            <div class="kt-alert-text">
                                Formda eksik ya da hatalı alanlar var. Lütfen kontrol edip tekrar gönder.
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('site.contact-messages.store') }}" class="grid gap-5" novalidate>
                        @csrf

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="kt-form-label mb-2">Mesajı göndereceğin kullanıcı</label>
                                <select
                                    id="contactRecipient"
                                    name="recipient_user_id"
                                    class="kt-select w-full @error('recipient_user_id') kt-input-invalid @enderror"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Kullanıcı seçin"
                                >
                                    <option value="">Kullanıcı seçin</option>
                                    @foreach($recipients as $recipient)
                                        <option
                                            value="{{ $recipient->id }}"
                                            @selected((int) old('recipient_user_id', $selectedRecipientId) === (int) $recipient->id)
                                        >
                                            {{ $recipient->name }}{{ $recipient->email ? ' - ' . $recipient->email : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('recipient_user_id')
                                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            @if($isMember)
                                <div class="md:col-span-2 rounded-2xl border border-success/20 bg-success/5 p-4">
                                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-foreground">Üye bilgilerin otomatik kullanılacak</div>
                                            <div class="text-sm text-muted-foreground">
                                                Bu formda sadece konu, öncelik ve mesaj içeriğini girmen yeterli.
                                            </div>
                                        </div>
                                        <span class="kt-badge kt-badge-sm kt-badge-light-success">Üye oturumu aktif</span>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl bg-white/80 px-4 py-3">
                                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Ad Soyad</div>
                                            <div class="mt-1 font-medium text-foreground">{{ $member->full_name }}</div>
                                        </div>
                                        <div class="rounded-xl bg-white/80 px-4 py-3">
                                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">İletişim</div>
                                            <div class="mt-1 font-medium text-foreground">{{ $member->email }}</div>
                                            <div class="text-sm text-muted-foreground">{{ $member->phone ?: 'Telefon bilgisi yok' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="md:col-span-2 rounded-2xl border border-border bg-muted/20 p-4">
                                    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-foreground">Gönderen bilgileri</div>
                                            <div class="text-sm text-muted-foreground">
                                                Ziyaretçi gönderimlerinde ad, soyad ve iletişim tercihi zorunludur.
                                            </div>
                                        </div>
                                        <span class="kt-badge kt-badge-sm kt-badge-light-primary">Ziyaretçi formu</span>
                                    </div>

                                    <div class="grid gap-5 md:grid-cols-2">
                                        <div>
                                            <label class="kt-form-label mb-2">Ad</label>
                                            <input
                                                type="text"
                                                name="name"
                                                value="{{ old('name') }}"
                                                class="kt-input w-full @error('name') kt-input-invalid @enderror"
                                                placeholder="Adınızı yazın"
                                            >
                                            @error('name')
                                                <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="kt-form-label mb-2">Soyad</label>
                                            <input
                                                type="text"
                                                name="surname"
                                                value="{{ old('surname') }}"
                                                class="kt-input w-full @error('surname') kt-input-invalid @enderror"
                                                placeholder="Soyadınızı yazın"
                                            >
                                            @error('surname')
                                                <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="kt-form-label mb-3">Size hangi kanaldan dönüş yapılsın?</label>
                                            <div class="grid gap-3 sm:grid-cols-2">
                                                @foreach($contactChannelOptions as $channelKey => $channelOption)
                                                    <label class="flex items-start gap-3 rounded-2xl border border-border bg-white px-4 py-4">
                                                        <input
                                                            type="checkbox"
                                                            name="contact_channels[]"
                                                            value="{{ $channelKey }}"
                                                            class="kt-checkbox kt-checkbox-sm mt-1"
                                                            data-contact-channel="{{ $channelKey }}"
                                                            @checked(in_array($channelKey, $selectedChannels, true))
                                                        >
                                                        <span class="flex flex-col">
                                                            <span class="text-sm font-medium text-foreground">{{ $channelOption['label'] }}</span>
                                                            <span class="text-sm text-muted-foreground">
                                                                {{ $channelKey === 'email' ? 'E-posta alanını doldurduğunda sana e-posta ile geri dönüş yapılır.' : 'Telefon alanını doldurduğunda sana telefon ile geri dönüş yapılır.' }}
                                                            </span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('contact_channels')
                                                <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div data-contact-field="email" class="{{ in_array('email', $selectedChannels, true) ? '' : 'hidden' }}">
                                            <label class="kt-form-label mb-2">E-posta</label>
                                            <input
                                                type="email"
                                                name="email"
                                                value="{{ old('email') }}"
                                                class="kt-input w-full @error('email') kt-input-invalid @enderror"
                                                placeholder="ornek@mail.com"
                                            >
                                            @error('email')
                                                <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div data-contact-field="phone" class="{{ in_array('phone', $selectedChannels, true) ? '' : 'hidden' }}">
                                            <label class="kt-form-label mb-2">Telefon</label>
                                            <input
                                                type="text"
                                                name="phone"
                                                value="{{ old('phone') }}"
                                                class="kt-input w-full @error('phone') kt-input-invalid @enderror"
                                                placeholder="05xx xxx xx xx"
                                            >
                                            @error('phone')
                                                <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="kt-form-label mb-2">Konu</label>
                                <input
                                    type="text"
                                    name="subject"
                                    value="{{ old('subject') }}"
                                    class="kt-input w-full @error('subject') kt-input-invalid @enderror"
                                    placeholder="Mesaj başlığını yazın"
                                >
                                @error('subject')
                                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="kt-form-label mb-2">Öncelik</label>
                                <select
                                    name="priority"
                                    class="kt-select w-full @error('priority') kt-input-invalid @enderror"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Öncelik seçin"
                                >
                                    @foreach($priorityOptions as $priorityKey => $priorityOption)
                                        <option
                                            value="{{ $priorityKey }}"
                                            @selected(old('priority', \App\Models\ContactMessage::PRIORITY_NORMAL) === $priorityKey)
                                        >
                                            {{ $priorityOption['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority')
                                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="kt-form-label mb-2">Mesaj</label>
                                <textarea
                                    name="message"
                                    rows="8"
                                    class="kt-textarea w-full @error('message') kt-input-invalid @enderror"
                                    placeholder="İhtiyacınızı, beklentinizi ya da yaşadığınız durumu detaylıca yazın"
                                >{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="mt-2 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-border/60 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-muted-foreground">
                                Mesajlar kayıt altına alınır. Gerekirse seçtiğin kullanıcı seninle belirttiğin kanaldan iletişime geçer.
                            </p>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                Mesajı Gönder
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid gap-6">
                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <h3 class="kt-card-title">Öncelik Rehberi</h3>
                    </div>
                    <div class="kt-card-content p-5 grid gap-4">
                        @foreach($priorityOptions as $priorityOption)
                            <div class="rounded-2xl border border-border bg-muted/10 p-4">
                                <span class="{{ $priorityOption['badge'] }}">{{ $priorityOption['label'] }}</span>
                                <p class="mt-3 text-sm text-muted-foreground">
                                    @switch($loop->index)
                                        @case(0)
                                            Bilgilendirme, genel soru ya da zaman baskısı olmayan konular için uygundur.
                                            @break
                                        @case(1)
                                            Standart iş akışı içinde değerlendirilmesi yeterli olan talepler için uygundur.
                                            @break
                                        @case(2)
                                            Gecikme yaşandığında iş akışını etkileyebilecek konular için tercih edilir.
                                            @break
                                        @default
                                            Kritik hata, acil geri dönüş ya da hızlı müdahale gerektiren durumlar için kullanılmalıdır.
                                    @endswitch
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <h3 class="kt-card-title">Sistem Notları</h3>
                    </div>
                    <div class="kt-card-content p-5 grid gap-4 text-sm text-muted-foreground">
                        <div class="rounded-2xl border border-border bg-white px-4 py-4">
                            Mesajı hangi kullanıcıya yönlendirdiğin admin panelde net şekilde görünür.
                        </div>
                        <div class="rounded-2xl border border-border bg-white px-4 py-4">
                            Süper admin tüm mesajları görebilir; diğer kullanıcılar sadece kendilerine gönderilen kayıtları görür.
                        </div>
                        <div class="rounded-2xl border border-border bg-white px-4 py-4">
                            İstersen ileride kullanıcı listesinde “bu kişiye mesaj gönder” butonu ekleyebiliriz; bu sayfa buna hazır.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@vite('resources/js/site/contact-messages/create.js')
