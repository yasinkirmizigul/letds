@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fluid py-6" data-page="appointments.calendar">

        {{-- Toolbar --}}
        <div class="kt-card mb-6">
            <div class="kt-card-body p-5 lg:p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">

                    <div class="flex flex-col gap-2 min-w-[280px]">
                        <label class="kt-form-label mb-1">Kişi</label>
                        <select id="providerSelect" class="kt-select w-full"
                            data-kt-select="true"

                        >
                            @foreach($providers as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} — {{ $p->title }}</option>
                            @endforeach
                        </select>
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

        {{-- Content --}}
        <div class="grid grid-cols-12 gap-6 items-start">
            <div class="col-span-12 2xl:col-span-8">
                <div class="kt-card">
                    <div class="kt-card-body p-4 lg:p-5">
                        <div id="appointmentsCalendar"></div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 2xl:col-span-4">
                <div class="kt-card">
                    <div class="kt-card-header px-5 py-4">
                        <h3 class="kt-card-title text-base font-semibold">Randevu Detayı</h3>
                    </div>

                    <div class="kt-card-body p-5 lg:p-6">
                        <div id="panelEmpty" class="text-sm text-gray-500">
                            Takvimden bir randevu seç.
                        </div>

                        <div id="panelContent" class="hidden flex flex-col gap-5">
                            <input type="hidden" id="selectedAppointmentId" value="">

                            <div class="space-y-1">
                                <div class="text-xs text-gray-500">Üye</div>
                                <div class="font-medium text-sm leading-6" id="pMember">-</div>
                            </div>

                            <div class="space-y-1">
                                <div class="text-xs text-gray-500">Tarih / Saat</div>
                                <div class="font-medium text-sm leading-6" id="pWhen">-</div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">Süre</div>
                                    <div class="font-medium text-sm" id="pDuration">-</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">Durum</div>
                                    <div class="font-medium text-sm" id="pStatus">-</div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">İptal Nedeni</div>
                                <textarea
                                    id="cancelReason"
                                    rows="4"
                                    class="kt-input w-full"
                                    placeholder="Opsiyonel açıklama"
                                ></textarea>
                            </div>

                            <div class="pt-1">
                                <button type="button" class="kt-btn kt-btn-danger" id="btnCancelAppointment">
                                    Randevuyu İptal Et
                                </button>
                            </div>

                            <div
                                class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-xs leading-5 text-gray-400">
                                Takvimde boş alan seçerek blokaj oluşturabilirsin. Randevuları veya blokajları sürükleyerek
                                taşıyabilir, alt kenarından uzatıp kısaltabilirsin.
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs text-gray-500">Geçmiş</div>
                                <div id="panelHistory" class="flex flex-col gap-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
