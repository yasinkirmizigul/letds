@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fluid py-6" data-page="appointments.calendar">

        <div class="kt-card mb-6 border border-gray-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="kt-card-body p-5 lg:p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">

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
                                <option value="" @selected(empty($selectedProviderId))>Tum kisiler</option>
                            @endif
                            @foreach($providers as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} — {{ $p->title }}</option>
                            @endforeach
                        </select>
                        <p id="calendarInteractionHint" class="hidden mt-2 text-xs text-amber-600 dark:text-amber-400">
                            Takvimi duzenlemek icin once kisi sec.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <button type="button" class="kt-btn kt-btn-light" data-cal="today">Bugün</button>
                            <button type="button" class="kt-btn kt-btn-light" data-cal="prev">‹</button>
                            <button type="button" class="kt-btn kt-btn-light" data-cal="next">›</button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" class="kt-btn kt-btn-light" data-view="timeGridDay">Gün</button>
                            <button type="button" class="kt-btn kt-btn-light" data-view="timeGridWeek">Hafta</button>
                            <button type="button" class="kt-btn kt-btn-light" data-view="dayGridMonth">Ay</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="kt-card mb-4 border border-gray-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="kt-card-body px-5 py-4">
                <div class="flex flex-wrap gap-3 text-xs">
            <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                <span class="h-3 w-3 rounded-full bg-blue-600"></span> Aktif Randevu
            </span>
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                <span class="h-3 w-3 rounded-full bg-amber-600"></span> Genel Kapalı
            </span>
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                <span class="h-3 w-3 rounded-full bg-green-600"></span> Mola
            </span>
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                <span class="h-3 w-3 rounded-full bg-violet-600"></span> Toplantı
            </span>
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-zinc-300">
                <span class="h-3 w-3 rounded-full bg-red-600"></span> İzin / İptal
            </span>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-12 gap-6 items-start">
            <div class="col-span-12 2xl:col-span-8">
                <div class="kt-card border border-gray-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="kt-card-body p-4 lg:p-5">
                        <div id="appointmentsCalendar"></div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 2xl:col-span-4">
                <div class="kt-card border border-gray-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="kt-card-header border-b border-gray-200 px-5 py-4 dark:border-zinc-800">
                        <h3 class="kt-card-title text-base font-semibold text-gray-900 dark:text-zinc-100">
                            Randevu Detayı
                        </h3>
                    </div>

                    <div class="kt-card-body p-5 lg:p-6">
                        <div id="panelEmpty" class="text-sm text-gray-500 dark:text-zinc-400">
                            Takvimden bir randevu seç.
                        </div>

                        <div id="panelContent" class="hidden flex flex-col gap-5">
                            <input type="hidden" id="selectedAppointmentId" value="">

                            <div class="space-y-1">
                                <div class="text-xs text-gray-500 dark:text-zinc-400">Üye / Kayıt</div>
                                <div class="font-medium text-sm leading-6 text-gray-900 dark:text-zinc-100"
                                     id="pMember">-
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="text-xs text-gray-500 dark:text-zinc-400">Tarih / Saat</div>
                                <div class="font-medium text-sm leading-6 text-gray-900 dark:text-zinc-100" id="pWhen">
                                    -
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500 dark:text-zinc-400">Süre</div>
                                    <div class="font-medium text-sm text-gray-900 dark:text-zinc-100" id="pDuration">-
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500 dark:text-zinc-400">Durum</div>
                                    <div class="font-medium text-sm text-gray-900 dark:text-zinc-100" id="pStatus">-
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="text-xs text-gray-500 dark:text-zinc-400">İptal Nedeni</div>
                                <textarea
                                    id="cancelReason"
                                    rows="4"
                                    class="kt-input w-full p-2 border-gray-300 bg-white text-gray-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 min-h-[44px]"
                                ></textarea>
                            </div>

                            <div class="pt-1">
                                <button type="button" class="kt-btn kt-btn-danger" id="btnCancelAppointment">
                                    Randevuyu İptal Et
                                </button>
                            </div>

                            <div class="space-y-2">
                                <div class="text-xs text-gray-500 dark:text-zinc-400">Geçmiş</div>
                                <div id="panelHistory" class="flex flex-col gap-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Block Modal --}}
        <div id="blockModal" class="hidden fixed inset-0 z-[9999]">
            <div class="absolute inset-0 bg-black/70" data-block-modal-close></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div
                    class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-950">
                    <div
                        class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-zinc-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100" id="blockModalTitle">
                            Takvim Blokajı
                        </h3>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-block-modal-close>Kapat
                        </button>
                    </div>

                    <div class="p-5 flex flex-col gap-4">
                        <input type="hidden" id="blockEntityId">
                        <input type="hidden" id="blockStartAt">
                        <input type="hidden" id="blockEndAt">

                        <div>
                            <label class="kt-form-label text-gray-700 dark:text-zinc-200 mb-3">Blok Tipi</label>
                            <select
                                id="blockType"
                                class="kt-select"
                            >
                                <option value="manual">Genel Kapalı</option>
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

                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                            <div>
                                <strong>Başlangıç:</strong>
                                <span id="blockStartPreview">-</span>
                            </div>
                            <div class="mt-1">
                                <strong>Bitiş:</strong>
                                <span id="blockEndPreview">-</span>
                            </div>
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

        {{-- Appointment Modal --}}
        <div id="appointmentModal" class="hidden fixed inset-0 z-[9998]">
            <div class="absolute inset-0 bg-black/70" data-appointment-modal-close></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div
                    class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-zinc-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100" id="appointmentModalTitle">
                            Randevu Detayı
                        </h3>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-appointment-modal-close>Kapat
                        </button>
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
                                <option value="">Mevcut kiside kalsin</option>
                                @foreach($transferProviders as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} — {{ $p->title }}</option>
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
                            <label class="kt-form-label mb-2">Yeni Başlangıç</label>
                            <input type="datetime-local" id="appointmentStartAt" class="kt-input w-full">
                        </div>

                        <div class="lg:col-span-2">
                            <label class="kt-form-label mb-2">İç Not</label>
                            <textarea id="appointmentNotesInternal" rows="3" class="kt-input w-full p-2 min-h-[44px]"></textarea>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="kt-form-label mb-2">İptal Nedeni</label>
                            <textarea id="appointmentCancelReason" rows="3" class="kt-input w-full p-2 min-h-[44px]"
                                      placeholder="Opsiyonel"></textarea>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 border-t border-gray-200 px-5 py-4 dark:border-zinc-800">
                        <button type="button" class="kt-btn kt-btn-danger" id="btnAppointmentCancel">
                            Randevuyu İptal Et
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

        {{-- Context Menu --}}
        <div
            id="calendarContextMenu"
            class="hidden fixed z-[10000] min-w-[180px] rounded-xl border border-gray-200 bg-white p-2 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950"
        >
            <button
                type="button"
                id="ctxEditBlock"
                class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-zinc-200 dark:hover:bg-zinc-900"
            >
                Blokajı Düzenle
            </button>

            <button
                type="button"
                id="ctxDeleteBlock"
                class="mt-1 flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-zinc-900"
            >
                Blokajı Sil
            </button>
        </div>

        {{-- Drag Tooltip --}}
        <div
            id="calendarDragTooltip"
            class="pointer-events-none hidden fixed z-[10001] rounded-lg bg-black px-3 py-2 text-xs font-medium text-white shadow-lg"
        ></div>

    </div>
@endsection
