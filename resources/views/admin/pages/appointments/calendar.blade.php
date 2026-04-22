@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-5 lg:gap-7.5" data-page="appointments.calendar">
        @includeIf('admin.partials._flash')

        <section class="app-shell-surface rounded-[28px] p-6 lg:p-7">
            <div class="grid gap-6 xl:grid-cols-[1.12fr,.88fr] xl:items-start">
                <div>
                    <div class="dashboard-kicker">Randevu takvimi</div>
                    <h1 class="mt-4 text-2xl font-semibold tracking-tight text-foreground lg:text-3xl">
                        Günü, haftayı ve takvim blokajlarını aynı operasyon panelinde yönet.
                    </h1>
                    <p class="mt-3 max-w-[72ch] text-sm leading-6 text-muted-foreground lg:text-base">
                        Seçili kişinin aktif randevularını, kapalı zaman bloklarını ve yaklaşan yoğunluğu anlık gör.
                        Taşıma, süre güncelleme, blokaj düzenleme ve iptal aksiyonları aynı akışta korunur.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="dashboard-chip">
                            <i class="ki-filled ki-calendar-8 text-[13px]"></i>
                            <span id="calendarCurrentRange">Takvim aralığı yükleniyor</span>
                        </span>
                        <span class="dashboard-chip">
                            <i class="ki-filled ki-calendar-tick text-[13px]"></i>
                            <span id="calendarCurrentViewLabel">Haftalık görünüm</span>
                        </span>
                        <span class="dashboard-chip">
                            <i class="ki-filled ki-user text-[13px]"></i>
                            <span id="calendarSelectedProviderName">{{ $canSelectProvider ? 'Tüm aktif kişiler' : auth()->user()?->name }}</span>
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,320px),1fr] lg:items-end">
                        <div class="flex flex-col gap-2 min-w-[280px]">
                            <label class="kt-form-label mb-1 text-gray-700 dark:text-zinc-200">Kişi</label>
                            <select
                                id="providerSelect"
                                data-initial-provider-id="{{ $selectedProviderId ?? '' }}"
                                class="kt-select"
                                data-kt-select="true"
                                @disabled(!$canSelectProvider)
                                data-kt-select-placeholder="Seçiniz"
                            >
                                @if($canSelectProvider)
                                    <option value="" data-provider-name="Tüm aktif kişiler" data-provider-title="Süper admin görünümü" @selected(empty($selectedProviderId))>
                                        Tüm kişiler
                                    </option>
                                @endif
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}" data-provider-name="{{ $provider->name }}" data-provider-title="{{ $provider->title }}">
                                        {{ $provider->name }} - {{ $provider->title }}
                                    </option>
                                @endforeach
                            </select>
                            <p id="calendarInteractionHint" class="hidden mt-2 text-xs text-amber-600 dark:text-amber-400">
                                Takvimi düzenlemek için önce kişi seç.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2">
                                <button type="button" class="kt-btn kt-btn-light" data-cal="today">Bugün</button>
                                <button type="button" class="kt-btn kt-btn-light" data-cal="prev">&#8249;</button>
                                <button type="button" class="kt-btn kt-btn-light" data-cal="next">&#8250;</button>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" class="kt-btn kt-btn-light" data-view="timeGridDay">Gün</button>
                                <button type="button" class="kt-btn kt-btn-light" data-view="timeGridWeek">Hafta</button>
                                <button type="button" class="kt-btn kt-btn-light" data-view="dayGridMonth">Ay</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <article class="rounded-3xl app-stat-card p-5">
                        <div class="text-sm text-muted-foreground">Görünen randevu</div>
                        <div class="mt-2 text-3xl font-semibold text-foreground" id="calendarMetricAppointments">-</div>
                        <div class="mt-2 text-xs text-muted-foreground">Seçili aralıktaki aktif rezervasyon sayısı</div>
                    </article>

                    <article class="rounded-3xl app-stat-card p-5">
                        <div class="text-sm text-muted-foreground">Görünen blokaj</div>
                        <div class="mt-2 text-3xl font-semibold text-warning" id="calendarMetricBlocks">-</div>
                        <div class="mt-2 text-xs text-muted-foreground">Mola, toplantı, izin ve manuel kapatma</div>
                    </article>

                    <article class="rounded-3xl app-stat-card p-5">
                        <div class="text-sm text-muted-foreground">Toplam dolu süre</div>
                        <div class="mt-2 text-3xl font-semibold text-success" id="calendarMetricBusyHours">-</div>
                        <div class="mt-2 text-xs text-muted-foreground">Takvimde randevu olarak dolu görünen süre</div>
                    </article>

                    <article class="rounded-3xl app-stat-card p-5">
                        <div class="text-sm text-muted-foreground">Sıradaki kayıt</div>
                        <div class="mt-2 text-lg font-semibold leading-7 text-foreground" id="calendarMetricNext">-</div>
                        <div class="mt-2 text-xs text-muted-foreground" id="calendarMetricHint">Yaklaşan kayıt bulunmuyor</div>
                    </article>
                </div>
            </div>
        </section>

        <div class="kt-card border border-gray-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="kt-card-body px-5 py-4">
                <div class="flex flex-wrap gap-3 text-xs">
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                        <span class="h-3 w-3 rounded-full bg-blue-600"></span> Aktif randevu
                    </span>
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                        <span class="h-3 w-3 rounded-full bg-amber-600"></span> Genel kapalı
                    </span>
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                        <span class="h-3 w-3 rounded-full bg-green-600"></span> Mola
                    </span>
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                        <span class="h-3 w-3 rounded-full bg-violet-600"></span> Toplantı
                    </span>
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                        <span class="h-3 w-3 rounded-full bg-red-600"></span> İzin / iptal
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6 items-start">
            <div class="col-span-12 2xl:col-span-8">
                <div class="kt-card border border-gray-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="kt-card-header py-5 flex-wrap gap-4">
                        <div>
                            <h3 class="kt-card-title">Operasyon takvimi</h3>
                            <div class="text-sm text-muted-foreground">
                                Sürükle-bırak, süre değiştirme ve blokaj yönetimi seçili kişiye göre burada çalışır.
                            </div>
                        </div>
                        <span class="kt-badge kt-badge-sm kt-badge-light" id="calendarSurfaceHint">Takvim hazırlanıyor</span>
                    </div>
                    <div class="kt-card-body p-4 lg:p-5">
                        <div id="appointmentsCalendar"></div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 2xl:col-span-4">
                <div class="grid gap-6">
                    <div class="kt-card border border-gray-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="kt-card-header border-b border-gray-200 px-5 py-4 dark:border-zinc-800">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="kt-card-title text-base font-semibold text-gray-900 dark:text-zinc-100">
                                        Seçili kayıt
                                    </h3>
                                    <div class="text-xs text-muted-foreground">Detay, not ve geçmiş akışına hızlı bakış</div>
                                </div>
                                <span class="kt-badge kt-badge-sm kt-badge-light" id="panelMetaBadge">Bekleniyor</span>
                            </div>
                        </div>

                        <div class="kt-card-body p-5 lg:p-6">
                            <div id="panelEmpty" class="rounded-2xl border border-dashed border-border bg-muted/15 px-4 py-5 text-sm text-muted-foreground">
                                Takvimden bir randevu veya blokaj seç.
                            </div>

                            <div id="panelContent" class="hidden flex flex-col gap-5">
                                <input type="hidden" id="selectedAppointmentId" value="">

                                <div class="rounded-2xl border border-border bg-background/70 px-4 py-4">
                                    <div class="text-xs text-muted-foreground">Kayıt / üye</div>
                                    <div class="mt-2 text-sm font-semibold leading-6 text-foreground" id="pMember">-</div>
                                    <div class="mt-2 text-xs text-muted-foreground" id="pProvider">-</div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <div class="text-xs text-muted-foreground">Zaman</div>
                                        <div class="text-sm font-medium leading-6 text-foreground" id="pWhen">-</div>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="text-xs text-muted-foreground">Süre</div>
                                        <div class="text-sm font-medium text-foreground" id="pDuration">-</div>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="text-xs text-muted-foreground">Durum</div>
                                        <div class="text-sm font-medium text-foreground" id="pStatus">-</div>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="text-xs text-muted-foreground">Akış notu</div>
                                        <div class="text-sm font-medium text-foreground" id="pTransfer">-</div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-xs text-muted-foreground">İç not / açıklama</div>
                                    <div class="rounded-2xl border border-border bg-background px-4 py-3 text-sm leading-6 text-foreground" id="pNotes">-</div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-xs text-muted-foreground" id="cancelReasonLabel">İptal nedeni</div>
                                        <div class="text-[11px] text-muted-foreground" id="cancelReasonHelp">İptal aksiyonunda bu alan gönderilir.</div>
                                    </div>
                                    <textarea
                                        id="cancelReason"
                                        rows="4"
                                        class="kt-input w-full p-2 border-gray-300 bg-white text-gray-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 min-h-[44px]"
                                    ></textarea>
                                </div>

                                <div class="pt-1">
                                    <button type="button" class="kt-btn kt-btn-danger" id="btnCancelAppointment">
                                        Randevuyu iptal et
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-xs text-muted-foreground">Geçmiş</div>
                                    <div id="panelHistory" class="flex flex-col gap-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="kt-card border border-gray-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="kt-card-header py-5">
                            <h3 class="kt-card-title">Operasyon notları</h3>
                        </div>
                        <div class="kt-card-body p-5">
                            <div class="grid gap-3 text-sm leading-6 text-muted-foreground">
                                <div class="rounded-2xl border border-border bg-background/70 px-4 py-3">
                                    Takvimde boş bir alan seçildiğinde yeni blokaj oluşturulur. Mevcut blokajlar düzenlenebilir ve silinebilir.
                                </div>
                                <div class="rounded-2xl border border-border bg-background/70 px-4 py-3">
                                    Randevu kutularını sürüklemek saati taşır. Kenardan çekmek süreyi değiştirir. Sistem görünen çakışmaları geri çevirir.
                                </div>
                                <div class="rounded-2xl border border-border bg-background/70 px-4 py-3">
                                    Modal üzerindeki güncelle akışı kişi değişikliği, saat taşıma ve iç not güncellemesini aynı işlemde toplar.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="blockModal" class="hidden fixed inset-0 z-[9999]">
            <div class="absolute inset-0 bg-black/70" data-block-modal-close></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-zinc-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100" id="blockModalTitle">
                            Takvim blokajı
                        </h3>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-block-modal-close>Kapat</button>
                    </div>

                    <div class="p-5 flex flex-col gap-4">
                        <input type="hidden" id="blockEntityId">
                        <input type="hidden" id="blockStartAt">
                        <input type="hidden" id="blockEndAt">

                        <div>
                            <label class="kt-form-label text-gray-700 dark:text-zinc-200 mb-3">Blok tipi</label>
                            <select id="blockType" class="kt-select">
                                <option value="manual">Genel kapalı</option>
                                <option value="break">Mola</option>
                                <option value="meeting">Toplantı</option>
                                <option value="off">İzin</option>
                            </select>
                        </div>

                        <div>
                            <label class="kt-form-label text-gray-700 dark:text-zinc-200 mb-3">Açıklama</label>
                            <input
                                type="text"
                                id="blockReason"
                                class="kt-input w-full border-gray-300 bg-white text-gray-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                placeholder="Örn: Öğle arası"
                            >
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                            <div><strong>Başlangıç:</strong> <span id="blockStartPreview">-</span></div>
                            <div class="mt-1"><strong>Bitiş:</strong> <span id="blockEndPreview">-</span></div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4 dark:border-zinc-800">
                        <button type="button" class="kt-btn kt-btn-light" data-block-modal-close>Vazgeç</button>
                        <button type="button" class="kt-btn kt-btn-primary" id="btnSaveBlock">
                            <span class="btn-text">Kaydet</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="appointmentModal" class="hidden fixed inset-0 z-[9998]">
            <div class="absolute inset-0 bg-black/70" data-appointment-modal-close></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-zinc-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100" id="appointmentModalTitle">
                            Randevu detayi
                        </h3>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-appointment-modal-close>Kapat</button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
                        <input type="hidden" id="appointmentEntityId">

                        <div>
                            <label class="kt-form-label mb-2">Üye</label>
                            <input type="text" id="appointmentMemberName" class="kt-input w-full" readonly>
                        </div>

                        <div>
                            <label class="kt-form-label mb-2">Durum</label>
                            <input type="text" id="appointmentStatusLabel" class="kt-input w-full" readonly>
                        </div>

                        <div>
                            <label class="kt-form-label mb-2">Kişi</label>
                            <select id="appointmentProviderId" class="kt-select">
                                <option value="">Mevcut kişide kalsin</option>
                                @foreach($transferProviders as $provider)
                                    <option value="{{ $provider->id }}" data-provider-name="{{ $provider->name }}" data-provider-title="{{ $provider->title }}">
                                        {{ $provider->name }} - {{ $provider->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="kt-form-label mb-2">Süre</label>
                            <select id="appointmentBlocks" class="kt-input w-full">
                                <option value="1">30 dk</option>
                                <option value="2">60 dk</option>
                                <option value="3">90 dk</option>
                                <option value="4">120 dk</option>
                                <option value="5">150 dk</option>
                                <option value="6">180 dk</option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="kt-form-label mb-2">Yeni başlangıç</label>
                            <div class="kt-input w-full">
                                <i class="ki-outline ki-calendar"></i>
                                <input
                                    id="appointmentStartAt"
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

                        <div class="lg:col-span-2">
                            <label class="kt-form-label mb-2">Ic not</label>
                            <textarea id="appointmentNotesInternal" rows="3" class="kt-input w-full p-2 min-h-[44px]"></textarea>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="kt-form-label mb-2">İptal nedeni</label>
                            <textarea id="appointmentCancelReason" rows="3" class="kt-input w-full p-2 min-h-[44px]" placeholder="Opsiyonel"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-gray-200 px-5 py-4 dark:border-zinc-800">
                        <button type="button" class="kt-btn kt-btn-danger" id="btnAppointmentCancel">
                            Randevuyu iptal et
                        </button>

                        <div class="flex items-center gap-2">
                            <button type="button" class="kt-btn kt-btn-light" data-appointment-modal-close>Vazgeç</button>
                            <button type="button" class="kt-btn kt-btn-primary" id="btnAppointmentSave">
                                <span class="btn-text">Güncelle</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            id="calendarContextMenu"
            class="hidden fixed z-[10000] min-w-[180px] rounded-xl border border-gray-200 bg-white p-2 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950"
        >
            <button
                type="button"
                id="ctxEditBlock"
                class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-zinc-200 dark:hover:bg-zinc-900"
            >
                Blokaji düzenle
            </button>

            <button
                type="button"
                id="ctxDeleteBlock"
                class="mt-1 flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-zinc-900"
            >
                Blokaji sil
            </button>
        </div>

        <div
            id="calendarDragTooltip"
            class="pointer-events-none hidden fixed z-[10001] rounded-lg bg-black px-3 py-2 text-xs font-medium text-white shadow-lg"
        ></div>
    </div>
@endsection
