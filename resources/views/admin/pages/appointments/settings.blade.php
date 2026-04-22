@extends('admin.layouts.main.app')

@section('content')
    <div
        class="kt-container-fixed max-w-[96%] grid gap-5 lg:gap-7.5"
        data-page="appointments.settings"
        data-provider-count="{{ $providerCount ?? 0 }}"
        data-timeoff-count="{{ $timeOffCount ?? 0 }}"
        data-blackout-count="{{ $blackoutCount ?? 0 }}"
    >
        @includeIf('admin.partials._flash')

        @if(($providers ?? collect())->isEmpty())
            <section class="kt-card">
                <div class="kt-card-content p-8 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <i class="ki-outline ki-calendar-add text-3xl"></i>
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-foreground">Randevu yönetecek aktif kişi bulunamadı</h2>
                    <p class="mx-auto mt-2 max-w-[56ch] text-sm leading-6 text-muted-foreground">
                        Bu ekranı kullanabilmek için aktif durumda olan ve provider, admin veya superadmin rolüne sahip
                        en az bir kullanıcı gerekiyor.
                    </p>
                </div>
            </section>
        @else
            <section class="app-shell-surface rounded-[28px] p-6 lg:p-7">
                <div class="grid gap-6 xl:grid-cols-[1.15fr,.85fr] xl:items-start">
                    <div>
                        <div class="dashboard-kicker">Randevu operasyonu</div>
                        <h1 class="mt-4 text-2xl font-semibold tracking-tight text-foreground lg:text-3xl">
                            Takvim ayarlarını tek merkezden yönetin.
                        </h1>
                        <p class="mt-3 max-w-[72ch] text-sm leading-6 text-muted-foreground lg:text-base">
                            Haftalık çalışma planını, kişisel kapalı zamanları, global blackout aralıklarını ve seçili
                            kişinin boş saat kapasitesini aynı panelde izleyip güncelleyebilirsin.
                        </p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <span class="dashboard-chip">
                                <i class="ki-filled ki-calendar-8 text-[13px]"></i>
                                Haftalık plan
                            </span>
                            <span class="dashboard-chip">
                                <i class="ki-filled ki-shield-tick text-[13px]"></i>
                                Çakışma kontrollü
                            </span>
                            <span class="dashboard-chip">
                                <i class="ki-filled ki-timer text-[13px]"></i>
                                30 dakikalık blok yapısı
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <article class="rounded-3xl app-stat-card p-5">
                            <div class="text-sm text-muted-foreground">Aktif takvim sahibi</div>
                            <div class="mt-2 text-3xl font-semibold text-foreground" id="settingsStatProviders">
                                {{ $providerCount ?? 0 }}
                            </div>
                            <div class="mt-2 text-xs text-muted-foreground">Plan yönetimi açık kişi sayısı</div>
                        </article>

                        <article class="rounded-3xl app-stat-card p-5">
                            <div class="text-sm text-muted-foreground">Seçili kişide açık gün</div>
                            <div class="mt-2 text-3xl font-semibold text-success" id="settingsStatEnabledDays">-</div>
                            <div class="mt-2 text-xs text-muted-foreground" id="settingsStatEnabledDaysHint">
                                Çalışma kapasitesi yükleniyor
                            </div>
                        </article>

                        <article class="rounded-3xl app-stat-card p-5">
                            <div class="text-sm text-muted-foreground">Seçili blokaj kaydı</div>
                            <div class="mt-2 text-3xl font-semibold text-warning" id="settingsStatTimeOffs">-</div>
                            <div class="mt-2 text-xs text-muted-foreground">Kişi bazlı kapalı zamanlar</div>
                        </article>

                        <article class="rounded-3xl app-stat-card p-5">
                            <div class="text-sm text-muted-foreground">Global blackout</div>
                            <div class="mt-2 text-3xl font-semibold text-primary" id="settingsStatBlackouts">
                                {{ $blackoutCount ?? 0 }}
                            </div>
                            <div class="mt-2 text-xs text-muted-foreground">Tüm takvimi etkileyen aralıklar</div>
                        </article>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 2xl:grid-cols-[1.15fr,.85fr]">
                <div class="grid gap-5">
                    <section class="kt-card">
                        <div class="kt-card-header py-5 flex-wrap gap-4">
                            <div>
                                <h3 class="kt-card-title">Kişi ve yönetim zemini</h3>
                                <div class="text-sm text-muted-foreground">
                                    Düzenleme yapacağın kişi seçildiğinde o kişinin haftalık ritmini ve sonraki blokajını anında gör.
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-light-primary" data-schedule-template="weekday_standard">
                                    Hafta içi 09:00-18:00
                                </button>
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-schedule-template="clinic_extended">
                                    Klinik vardiyası
                                </button>
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-light-danger" data-schedule-template="clear_all">
                                    Tümünü kapat
                                </button>
                            </div>
                        </div>

                        <div class="kt-card-content p-5">
                            <div class="grid gap-4 xl:grid-cols-[minmax(0,300px),1fr] xl:items-start">
                                <div class="space-y-3">
                                    <label class="kt-form-label">Kişi seçimi</label>
                                    <select id="settingsProviderSelect" class="kt-select w-full" data-kt-select="true" data-kt-select-placeholder="Kişi seç">
                                        @foreach($providers as $provider)
                                            <option
                                                value="{{ $provider->id }}"
                                                data-provider-name="{{ $provider->name }}"
                                                data-provider-title="{{ $provider->title }}"
                                            >
                                                {{ $provider->name }} - {{ $provider->title ?: 'Ünvan tanımsız' }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button type="button" class="kt-btn kt-btn-primary w-full" id="btnSaveWorkingHours">
                                        Çalışma saatlerini kaydet
                                    </button>

                                    <div class="rounded-3xl border border-border bg-muted/25 p-4">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                <i class="ki-outline ki-user-square text-xl"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <div class="truncate text-base font-semibold text-foreground" id="settingsProviderName">-</div>
                                                <div class="truncate text-sm text-muted-foreground" id="settingsProviderTitle">-</div>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                            <div class="rounded-2xl border border-border bg-background px-4 py-3">
                                                <div class="text-xs uppercase tracking-wide text-muted-foreground">Haftalık kapasite</div>
                                                <div class="mt-2 text-lg font-semibold text-foreground" id="settingsWeeklyHours">-</div>
                                            </div>

                                            <div class="rounded-2xl border border-border bg-background px-4 py-3">
                                                <div class="text-xs uppercase tracking-wide text-muted-foreground">Sonraki blokaj</div>
                                                <div class="mt-2 text-sm font-medium leading-6 text-foreground" id="settingsNextTimeOff">Planlanmış blokaj yok</div>
                                            </div>
                                        </div>

                                        <div class="mt-4 rounded-2xl border border-dashed border-border bg-background/70 px-4 py-3 text-sm leading-6 text-muted-foreground" id="settingsCoverageText">
                                            Kişi seçildiğinde çalışma günleri ve blackout etkisi burada özetlenir.
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-border bg-background/70 p-4 lg:p-5">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-foreground">Plan notları</div>
                                            <div class="text-xs text-muted-foreground">
                                                Şablon butonları sadece formu doldurur; kalıcı hale gelmesi için kaydetmen gerekir.
                                            </div>
                                        </div>
                                        <span class="kt-badge kt-badge-sm kt-badge-light-primary" id="settingsPlanBadge">Kayıtlar yükleniyor</span>
                                    </div>

                                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                                        <div class="rounded-2xl border border-border bg-background px-4 py-3">
                                            <div class="text-xs uppercase tracking-wide text-muted-foreground">Açık gün</div>
                                            <div class="mt-2 text-xl font-semibold text-foreground" id="scheduleEnabledDaysCount">-</div>
                                        </div>
                                        <div class="rounded-2xl border border-border bg-background px-4 py-3">
                                            <div class="text-xs uppercase tracking-wide text-muted-foreground">Toplam süre</div>
                                            <div class="mt-2 text-xl font-semibold text-foreground" id="scheduleWeeklyHours">-</div>
                                        </div>
                                        <div class="rounded-2xl border border-border bg-background px-4 py-3">
                                            <div class="text-xs uppercase tracking-wide text-muted-foreground">Kapsama</div>
                                            <div class="mt-2 text-sm font-semibold text-foreground" id="scheduleCoverageLabel">-</div>
                                        </div>
                                    </div>

                                    <div class="mt-4 rounded-2xl border border-dashed border-border bg-muted/20 px-4 py-3 text-sm leading-6 text-muted-foreground" id="scheduleTemplateHint">
                                        Pazartesi-cumartesi akışını işletmek, molaları blokaj olarak görmek ve boş günleri net ayırmak için önce
                                        bir şablon seçip sonra ihtiyaca göre ince ayar yapabilirsin.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="kt-card">
                        <div class="kt-card-header py-5 flex-wrap gap-4">
                            <div>
                                <h3 class="kt-card-title">Haftalık çalışma saatleri</h3>
                                <div class="text-sm text-muted-foreground">
                                    Her gün için açık-kapalı durumu ve saat aralığını gör. Özet alanları form değiştikçe anında güncellenir.
                                </div>
                            </div>
                            <span class="kt-badge kt-badge-sm kt-badge-light" id="workingHoursStatusBadge">Kayıt bekleniyor</span>
                        </div>

                        <div class="kt-card-content p-5">
                            <div class="kt-scrollable-x-auto">
                                <table class="kt-table kt-table-border w-full align-middle">
                                    <thead>
                                    <tr>
                                        <th class="min-w-[180px]">Gün</th>
                                        <th class="min-w-[120px]">Durum</th>
                                        <th class="min-w-[150px]">Başlangıç</th>
                                        <th class="min-w-[150px]">Bitiş</th>
                                        <th class="min-w-[140px]">Günlük süre</th>
                                    </tr>
                                    </thead>
                                    <tbody id="workingHoursBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="grid gap-5">
                    <section class="kt-card">
                        <div class="kt-card-header py-5 flex-wrap gap-4">
                            <div>
                                <h3 class="kt-card-title">Kişisel kapalı zaman</h3>
                                <div class="text-sm text-muted-foreground">
                                    İzin, mola, toplantı ya da manuel kapatma bloklarını kişi bazında yönet.
                                </div>
                            </div>
                            <span class="kt-badge kt-badge-sm kt-badge-light-warning" id="timeOffCountBadge">0 kayıt</span>
                        </div>

                        <div class="kt-card-content p-5">
                            <input type="hidden" id="timeOffEditingId" value="">

                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="kt-form-label mb-2">Başlangıç</label>
                                    <div class="kt-input w-full">
                                        <i class="ki-outline ki-calendar"></i>
                                        <input
                                            id="timeOffStart"
                                            class="grow"
                                            type="text"
                                            readonly
                                            placeholder="GG.AA.YYYY SS:DD"
                                            data-app-date-picker="true"
                                            data-app-date-mode="datetime"
                                            data-kt-date-picker="true"
                                            data-kt-date-picker-input-mode="true"
                                            data-kt-date-picker-position-to-input="left"
                                            data-kt-date-picker-selection-time-mode="24"
                                            data-kt-date-picker-locale="tr-TR"
                                            data-kt-date-picker-first-weekday="1"
                                            data-kt-date-picker-date-format="DD.MM.YYYY HH:mm"
                                        >
                                    </div>
                                </div>
                                <div>
                                    <label class="kt-form-label mb-2">Bitiş</label>
                                    <div class="kt-input w-full">
                                        <i class="ki-outline ki-calendar"></i>
                                        <input
                                            id="timeOffEnd"
                                            class="grow"
                                            type="text"
                                            readonly
                                            placeholder="GG.AA.YYYY SS:DD"
                                            data-app-date-picker="true"
                                            data-app-date-mode="datetime"
                                            data-kt-date-picker="true"
                                            data-kt-date-picker-input-mode="true"
                                            data-kt-date-picker-position-to-input="left"
                                            data-kt-date-picker-selection-time-mode="24"
                                            data-kt-date-picker-locale="tr-TR"
                                            data-kt-date-picker-first-weekday="1"
                                            data-kt-date-picker-date-format="DD.MM.YYYY HH:mm"
                                        >
                                    </div>
                                </div>
                                <div>
                                    <label class="kt-form-label mb-2">Blok tipi</label>
                                    <select id="timeOffType" class="kt-select w-full" data-kt-select="true" data-kt-select-placeholder="Blok tipi">
                                        <option value="manual">Genel kapalı</option>
                                        <option value="break">Mola</option>
                                        <option value="meeting">Toplantı</option>
                                        <option value="off">İzin</option>
                                    </select>
                                </div>
                                <div class="md:col-span-1">
                                    <label class="kt-form-label mb-2">Açıklama</label>
                                    <input type="text" id="timeOffReason" class="kt-input w-full" placeholder="Örn: Öğle arası">
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <button type="button" class="kt-btn kt-btn-light-primary" id="btnAddTimeOff">
                                    Kişisel blok ekle
                                </button>
                                <button type="button" class="kt-btn kt-btn-light hidden" id="btnCancelTimeOffEdit">
                                    Düzenlemeyi iptal et
                                </button>
                            </div>

                            <div class="mt-5 flex flex-col gap-3" id="timeOffList"></div>
                        </div>
                    </section>

                    <section class="kt-card">
                        <div class="kt-card-header py-5 flex-wrap gap-4">
                            <div>
                                <h3 class="kt-card-title">Global blackout aralıklari</h3>
                                <div class="text-sm text-muted-foreground">
                                    Tatil, bakım ya da kurumsal kapama günlerini tüm kişiler için merkezi olarak belirle.
                                </div>
                            </div>
                            <span class="kt-badge kt-badge-sm kt-badge-light-danger" id="blackoutCountBadge">
                                {{ $blackoutCount ?? 0 }} kayıt
                            </span>
                        </div>

                        <div class="kt-card-content p-5">
                            <input type="hidden" id="blackoutEditingId" value="">

                            <div class="grid gap-3">
                                <div>
                                    <label class="kt-form-label mb-2">Etiket</label>
                                    <input type="text" id="blackoutLabel" class="kt-input w-full" placeholder="Örn: Resmi tatil">
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <label class="kt-form-label mb-2">Başlangıç</label>
                                        <div class="kt-input w-full">
                                            <i class="ki-outline ki-calendar"></i>
                                            <input
                                                id="blackoutStart"
                                                class="grow"
                                                type="text"
                                                readonly
                                                placeholder="GG.AA.YYYY SS:DD"
                                                data-app-date-picker="true"
                                                data-app-date-mode="datetime"
                                                data-kt-date-picker="true"
                                                data-kt-date-picker-input-mode="true"
                                                data-kt-date-picker-position-to-input="left"
                                                data-kt-date-picker-selection-time-mode="24"
                                                data-kt-date-picker-locale="tr-TR"
                                                data-kt-date-picker-first-weekday="1"
                                                data-kt-date-picker-date-format="DD.MM.YYYY HH:mm"
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <label class="kt-form-label mb-2">Bitiş</label>
                                        <div class="kt-input w-full">
                                            <i class="ki-outline ki-calendar"></i>
                                            <input
                                                id="blackoutEnd"
                                                class="grow"
                                                type="text"
                                                readonly
                                                placeholder="GG.AA.YYYY SS:DD"
                                                data-app-date-picker="true"
                                                data-app-date-mode="datetime"
                                                data-kt-date-picker="true"
                                                data-kt-date-picker-input-mode="true"
                                                data-kt-date-picker-position-to-input="left"
                                                data-kt-date-picker-selection-time-mode="24"
                                                data-kt-date-picker-locale="tr-TR"
                                                data-kt-date-picker-first-weekday="1"
                                                data-kt-date-picker-date-format="DD.MM.YYYY HH:mm"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <button type="button" class="kt-btn kt-btn-light-danger" id="btnAddBlackout">
                                    Global blackout ekle
                                </button>
                                <button type="button" class="kt-btn kt-btn-light hidden" id="btnCancelBlackoutEdit">
                                    Düzenlemeyi iptal et
                                </button>
                            </div>

                            <div class="mt-5 flex flex-col gap-3" id="blackoutList"></div>
                        </div>
                    </section>

                    <section class="kt-card">
                        <div class="kt-card-header py-5 flex-wrap gap-4">
                            <div>
                                <h3 class="kt-card-title">Uygunluk laboratuvarı</h3>
                                <div class="text-sm text-muted-foreground">
                                    Seçili kişi için belirli bir gün ve süre kombinasyonunda boş saatleri test et.
                                </div>
                            </div>
                            <span class="kt-badge kt-badge-sm kt-badge-light-success" id="availabilityCountBadge">Hazır</span>
                        </div>

                        <div class="kt-card-content p-5">
                            <div class="grid gap-3 md:grid-cols-[1fr,170px,auto] md:items-end">
                                <div>
                                    <label class="kt-form-label mb-2">Tarih</label>
                                    <div class="kt-input w-full">
                                        <i class="ki-outline ki-calendar"></i>
                                        <input
                                            id="availabilityDate"
                                            class="grow"
                                            type="text"
                                            readonly
                                            placeholder="GG.AA.YYYY"
                                            data-app-date-picker="true"
                                            data-app-date-mode="date"
                                            data-kt-date-picker="true"
                                            data-kt-date-picker-input-mode="true"
                                            data-kt-date-picker-position-to-input="left"
                                            data-kt-date-picker-locale="tr-TR"
                                            data-kt-date-picker-first-weekday="1"
                                            data-kt-date-picker-date-format="DD.MM.YYYY"
                                        >
                                    </div>
                                </div>
                                <div>
                                    <label class="kt-form-label mb-2">Süre</label>
                                    <select id="availabilityBlocks" class="kt-select w-full" data-kt-select="true" data-kt-select-placeholder="Süre">
                                        <option value="1">30 dk</option>
                                        <option value="2">60 dk</option>
                                        <option value="3">90 dk</option>
                                        <option value="4">120 dk</option>
                                        <option value="5">150 dk</option>
                                        <option value="6">180 dk</option>
                                    </select>
                                </div>
                                <button type="button" class="kt-btn kt-btn-success" id="btnCheckAvailability">
                                    Uygun saatleri getir
                                </button>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div class="rounded-2xl border border-border bg-background px-4 py-3">
                                    <div class="text-xs uppercase tracking-wide text-muted-foreground">Bulunan slot</div>
                                    <div class="mt-2 text-2xl font-semibold text-foreground" id="availabilitySlotCount">-</div>
                                </div>
                                <div class="rounded-2xl border border-border bg-background px-4 py-3">
                                    <div class="text-xs uppercase tracking-wide text-muted-foreground">Durum özeti</div>
                                    <div class="mt-2 text-sm font-medium leading-6 text-foreground" id="availabilitySummaryText">
                                        Tarih ve süre seçildiğinde uygunluk burada hesaplanır.
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2" id="availabilityList"></div>
                        </div>
                    </section>
                </div>
            </div>
        @endif
    </div>
@endsection
