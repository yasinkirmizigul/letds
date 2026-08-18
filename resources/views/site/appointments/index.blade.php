@extends('site.layouts.main.app')

@push('site_vendor_css')
    <link rel="stylesheet" href="{{ asset('assets/site/plugins/global/plugins.bundle.css') }}">
@endpush

@push('site_vendor_js')
    <script src="{{ asset('assets/site/plugins/global/plugins.bundle.js') }}"></script>
@endpush

@php($pageTitle = 'Ön Görüşme Randevusu')

@section('content')
    <div class="site-appointment-page">
        <div class="site-appointment-page__inner">
            @include('site.partials.member-nav')

            <header class="site-appointment-heading">
                <div>
                    <span class="site-appointment-kicker">Ücretsiz Ön Görüşme</span>
                    <h1>Projeniz için doğru başlangıcı birlikte planlayalım.</h1>
                    <p>Üyelik bilgilerinizi kontrol edin, uzman ve uygun saati seçin, ardından randevu özetini onaylayın.</p>
                </div>
                <div class="site-appointment-heading__meta">
                    <span><i class="fa-solid fa-clock"></i> Yaklaşık 2 dakika</span>
                    <span><i class="fa-solid fa-shield-halved"></i> Güvenli randevu</span>
                </div>
            </header>

            @if($activeAppointment)
                <section class="site-active-appointment" id="active-appointment-card">
                    <span class="site-active-appointment__icon"><i class="fa-solid fa-calendar-check"></i></span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-foreground">Aktif randevunuz bulunuyor</div>
                        <div class="mt-1 font-display text-2xl font-semibold text-foreground">
                            {{ $activeAppointment->start_at->format('d.m.Y H:i') }}
                        </div>
                        <div class="mt-1 text-sm text-muted-foreground">Yeni seçim yapmak için mevcut randevunuzu yeniden planlayabilirsiniz.</div>
                        @if($activeAppointment->meetingMethod)
                            <div class="mt-2 text-sm font-medium text-foreground">
                                <i class="fa-solid fa-comments mr-1 text-primary"></i>
                                {{ $activeAppointment->meetingMethod->name }}
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button id="cancelBtn" class="kt-btn kt-btn-danger">İptal Et</button>
                        <button id="rescheduleBtn" class="kt-btn kt-btn-primary">Yeniden Planla</button>
                    </div>
                </section>

                <script>
                    window.__HAS_ACTIVE_APPOINTMENT__ = true;
                    window.__ACTIVE_APPOINTMENT_ID__ = {{ $activeAppointment->id }};
                    window.__RESCHEDULE_MODE__ = false;
                </script>
            @else
                <script>
                    window.__HAS_ACTIVE_APPOINTMENT__ = false;
                    window.__ACTIVE_APPOINTMENT_ID__ = null;
                    window.__RESCHEDULE_MODE__ = false;
                </script>
            @endif

            <section id="booking-panel" class="site-appointment-shell {{ $activeAppointment ? 'hidden' : '' }}">
                <div id="reschedule-mode-banner" class="site-appointment-banner hidden">
                    <i class="fa-solid fa-rotate"></i>
                    Yeniden planlama modundasınız. Yeni seçim önceki randevunun yerine geçecek.
                </div>

                <nav class="site-appointment-stepper" aria-label="Randevu adımları">
                    @foreach([
                        1 => ['Bilgileriniz', 'Üyelik özeti'],
                        2 => ['Tarih ve Saat', 'Uzman ve uygunluk'],
                        3 => ['Onay', 'Randevu önizlemesi'],
                    ] as $step => [$title, $description])
                        <button type="button" class="site-appointment-step" data-appointment-step="{{ $step }}" aria-current="{{ $step === 1 ? 'step' : 'false' }}">
                            <span class="site-appointment-step__number">{{ $step }}</span>
                            <span>
                                <strong>{{ $title }}</strong>
                                <small>{{ $description }}</small>
                            </span>
                        </button>
                    @endforeach
                </nav>

                <div class="site-appointment-panels">
                    <div data-appointment-step-panel="1" class="site-appointment-panel">
                        <div class="site-appointment-panel__heading">
                            <span class="site-appointment-panel__icon"><i class="fa-solid fa-circle-user"></i></span>
                            <div>
                                <h2>Üyelik bilgilerinizi kontrol edin</h2>
                                <p>Randevu mevcut hesabınızla eşleştirilecek; bilgileri yeniden girmeniz gerekmiyor.</p>
                            </div>
                        </div>

                        <div class="site-appointment-intake">
                            <div class="site-member-summary">
                                <article><span>Ad Soyad</span><strong>{{ $member->full_name ?: 'Belirtilmemiş' }}</strong></article>
                                <article><span>E-posta</span><strong>{{ $member->email ?: 'Belirtilmemiş' }}</strong></article>
                                <article><span>Telefon</span><strong>{{ $member->phone ?: 'Belirtilmemiş' }}</strong></article>
                                <article><span>Kurum / Üniversite</span><strong>{{ $member->institution ?: 'Belirtilmemiş' }}</strong></article>
                            </div>

                            <div class="site-appointment-preferences">
                                <div>
                                    <span class="site-appointment-kicker">Görüşme Tercihi</span>
                                    <h3>Nasıl görüşmek istersiniz?</h3>
                                    <p>Uygun görüşme kanalını seçip dilerseniz kısa bir ön bilgi paylaşın.</p>
                                </div>

                                @if($meetingMethods->isEmpty())
                                    <div class="site-appointment-empty">
                                        <i class="fa-solid fa-circle-info"></i>
                                        Şu anda seçilebilir bir görüşme yöntemi bulunmuyor.
                                    </div>
                                @else
                                    <label class="grid gap-2" for="meetingMethod">
                                        <span class="kt-form-label">Görüşme yöntemi</span>
                                        <select id="meetingMethod" class="kt-select w-full" required>
                                            @foreach($meetingMethods as $meetingMethod)
                                                <option
                                                    value="{{ $meetingMethod->id }}"
                                                    data-description="{{ $meetingMethod->description }}"
                                                    @selected((int) ($activeAppointment?->meeting_method_id ?? 0) === (int) $meetingMethod->id)
                                                >
                                                    {{ $meetingMethod->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small id="meetingMethodDescription" class="text-sm leading-6 text-muted-foreground"></small>
                                    </label>

                                    <label class="grid gap-2" for="appointmentMemberNote">
                                        <span class="kt-form-label">Eklemek istediğiniz not <span class="font-normal text-muted-foreground">(isteğe bağlı)</span></span>
                                        <textarea id="appointmentMemberNote" class="kt-textarea min-h-28" maxlength="2000" placeholder="Görüşme öncesinde uzmanın bilmesini istediğiniz kısa notu yazabilirsiniz.">{{ $activeAppointment?->notes_member }}</textarea>
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div class="site-appointment-panel__footer">
                            <a href="{{ route('member.account.edit') }}" class="kt-btn kt-btn-light" title="Üyelik bilgilerini düzenle">
                                <i class="fa-solid fa-pen"></i> Bilgilerimi Düzenle
                            </a>
                            <button type="button" id="appointmentStep1Next" class="kt-btn kt-btn-primary" @disabled($meetingMethods->isEmpty())>
                                Tarih ve Saate Geç <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <div data-appointment-step-panel="2" class="site-appointment-panel hidden">
                        <div class="site-appointment-panel__heading">
                            <span class="site-appointment-panel__icon"><i class="fa-solid fa-calendar-days"></i></span>
                            <div>
                                <h2>Size uygun uzmanı ve zamanı seçin</h2>
                                <p>Takvim yalnızca seçtiğiniz uzmanın gerçekten müsait olduğu saatleri gösterir.</p>
                            </div>
                        </div>

                        @if($providers->isEmpty())
                            <div class="site-appointment-empty">
                                <i class="fa-solid fa-circle-info"></i>
                                Şu anda çevrim içi randevuya açık uzman bulunmuyor. Lütfen daha sonra tekrar deneyin.
                            </div>
                        @else
                            <div class="site-appointment-schedule">
                                <div class="site-appointment-provider">
                                    <label class="kt-form-label" for="provider">Görüşeceğiniz uzman</label>
                                    <select id="provider" class="kt-select w-full">
                                        @foreach($providers as $provider)
                                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                    <p>Uzman değiştiğinde uygun takvim otomatik yenilenir.</p>
                                </div>

                                <div class="site-appointment-calendar-card">
                                    <div class="site-appointment-calendar-card__header">
                                        <button type="button" id="prevMonthBtn" class="kt-btn kt-btn-icon kt-btn-light" aria-label="Önceki ay"><i class="fa-solid fa-chevron-left"></i></button>
                                        <div id="calendarTitle" class="font-semibold text-foreground"></div>
                                        <button type="button" id="nextMonthBtn" class="kt-btn kt-btn-icon kt-btn-light" aria-label="Sonraki ay"><i class="fa-solid fa-chevron-right"></i></button>
                                    </div>
                                    <div id="calendar"></div>
                                </div>

                                <div class="site-appointment-slots-card">
                                    <div class="grid gap-2">
                                        <label class="kt-form-label" for="date">Seçili tarih</label>
                                        <div class="kt-input w-full">
                                            <i class="fa-solid fa-calendar-days"></i>
                                            <input id="date" class="grow" type="text" readonly placeholder="GG.AA.YYYY" data-app-date-picker="true" data-app-date-mode="date" data-app-date-format="DD.MM.YYYY">
                                        </div>
                                    </div>
                                    <div class="mt-5">
                                        <div class="mb-2 text-sm font-semibold text-foreground">Uygun saatler</div>
                                        <div id="slots" class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
                                        <div id="slot-empty" class="mt-3 hidden text-sm text-muted-foreground">Bu gün için uygun saat bulunamadı. Başka bir gün seçmeyi deneyin.</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="site-appointment-panel__footer">
                            <button type="button" id="appointmentStep2Back" class="kt-btn kt-btn-light"><i class="fa-solid fa-arrow-left"></i> Bilgilere Dön</button>
                            <button type="button" id="appointmentStep2Next" class="kt-btn kt-btn-primary" disabled>Randevuyu Önizle <i class="fa-solid fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <div data-appointment-step-panel="3" class="site-appointment-panel hidden">
                        <div class="site-appointment-panel__heading">
                            <span class="site-appointment-panel__icon"><i class="fa-solid fa-circle-check"></i></span>
                            <div>
                                <h2>Randevunuzu onaylayın</h2>
                                <p>Kaydetmeden önce iletişim ve görüşme bilgilerinizi son kez kontrol edin.</p>
                            </div>
                        </div>

                        <div class="site-appointment-preview">
                            <div class="site-appointment-preview__member">
                                <span class="site-appointment-preview__avatar">{{ mb_strtoupper(mb_substr($member->name ?: 'Ü', 0, 1)) }}</span>
                                <div><span>Görüşme sahibi</span><strong>{{ $member->full_name }}</strong><small>{{ $member->email }}</small></div>
                            </div>
                            <dl>
                                <div><dt>İşlem</dt><dd id="appointmentPreviewMode">Yeni ön görüşme</dd></div>
                                <div><dt>Görüşme yöntemi</dt><dd id="appointmentPreviewMeetingMethod">-</dd></div>
                                <div><dt>Uzman</dt><dd id="appointmentPreviewProvider">-</dd></div>
                                <div><dt>Tarih</dt><dd id="appointmentPreviewDate">-</dd></div>
                                <div><dt>Saat</dt><dd id="appointmentPreviewTime">-</dd></div>
                                <div><dt>Kurum / Üniversite</dt><dd>{{ $member->institution ?: 'Belirtilmemiş' }}</dd></div>
                                <div><dt>Ön görüşme notu</dt><dd id="appointmentPreviewMemberNote">Not eklenmedi</dd></div>
                                <div><dt>Süre</dt><dd>Standart görüşme süresi</dd></div>
                            </dl>
                        </div>

                        <div class="site-appointment-consent">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Onayladığınızda randevu hesabınıza kaydedilir ve ilgili uzmana bildirim gönderilir.</span>
                        </div>

                        <div class="site-appointment-panel__footer">
                            <button type="button" id="appointmentStep3Back" class="kt-btn kt-btn-light"><i class="fa-solid fa-arrow-left"></i> Seçimi Değiştir</button>
                            <button type="button" id="appointmentSubmit" class="kt-btn kt-btn-primary"><i class="fa-solid fa-check"></i> <span data-submit-label>Randevuyu Onayla</span></button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('site_js')
    @vite('resources/js/site/appointments/index.js')
@endpush
