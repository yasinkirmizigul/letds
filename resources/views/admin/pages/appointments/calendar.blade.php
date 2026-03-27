@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fluid" data-page="appointments.calendar">

        <div class="kt-card mb-4">
            <div class="kt-card-body flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

                <div class="min-w-[260px]">
                    <label class="kt-form-label">Kişi</label>
                    <select id="providerSelect" class="kt-input w-full">
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} — {{ $p->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" class="kt-btn kt-btn-light" data-cal="today">Bugün</button>
                    <button type="button" class="kt-btn kt-btn-light" data-cal="prev">‹</button>
                    <button type="button" class="kt-btn kt-btn-light" data-cal="next">›</button>

                    <button type="button" class="kt-btn kt-btn-light" data-view="timeGridDay">Gün</button>
                    <button type="button" class="kt-btn kt-btn-light" data-view="timeGridWeek">Hafta</button>
                    <button type="button" class="kt-btn kt-btn-light" data-view="dayGridMonth">Ay</button>
                </div>

            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 lg:col-span-8">
                <div class="kt-card">
                    <div class="kt-card-body">
                        <div id="appointmentsCalendar"></div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Randevu Detayı</h3>
                    </div>
                    <div class="kt-card-body">
                        <div id="panelEmpty" class="text-sm text-gray-500">
                            Takvimden bir randevu seç.
                        </div>

                        <div id="panelContent" class="hidden flex flex-col gap-3">
                            <div>
                                <div class="text-xs text-gray-500">Üye</div>
                                <div class="font-medium" id="pMember">-</div>
                            </div>

                            <div>
                                <div class="text-xs text-gray-500">Tarih / Saat</div>
                                <div class="font-medium" id="pWhen">-</div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <div class="text-xs text-gray-500">Süre</div>
                                    <div class="font-medium" id="pDuration">-</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Durum</div>
                                    <div class="font-medium" id="pStatus">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
