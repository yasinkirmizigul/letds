@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fluid" data-page="appointments.settings">

        <div class="kt-card mb-4">
            <div class="kt-card-body flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div class="min-w-[280px]">
                    <label class="kt-form-label">Kişi</label>
                    <select id="settingsProviderSelect" class="kt-input w-full">
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} — {{ $p->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button type="button" class="kt-btn kt-btn-primary" id="btnSaveWorkingHours">
                        Çalışma Saatlerini Kaydet
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 xl:col-span-6">
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Haftalık Çalışma Saatleri</h3>
                    </div>
                    <div class="kt-card-body">
                        <div class="overflow-x-auto">
                            <table class="kt-table">
                                <thead>
                                <tr>
                                    <th>Gün</th>
                                    <th>Açık</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                </tr>
                                </thead>
                                <tbody id="workingHoursBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="kt-card mt-4">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Kişisel Kapalı Zaman</h3>
                    </div>
                    <div class="kt-card-body flex flex-col gap-3">
                        <input type="datetime-local" id="timeOffStart" class="kt-input">
                        <input type="datetime-local" id="timeOffEnd" class="kt-input">
                        <input type="text" id="timeOffReason" class="kt-input" placeholder="Açıklama">
                        <button type="button" class="kt-btn kt-btn-light-primary" id="btnAddTimeOff">Ekle</button>

                        <div id="timeOffList" class="flex flex-col gap-2"></div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-6">
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Global Kapalı Zamanlar</h3>
                    </div>
                    <div class="kt-card-body flex flex-col gap-3">
                        <input type="text" id="blackoutLabel" class="kt-input" placeholder="Örn: Ramazan Bayramı">
                        <input type="datetime-local" id="blackoutStart" class="kt-input">
                        <input type="datetime-local" id="blackoutEnd" class="kt-input">
                        <button type="button" class="kt-btn kt-btn-light-danger" id="btnAddBlackout">Ekle</button>

                        <div id="blackoutList" class="flex flex-col gap-2"></div>
                    </div>
                </div>

                <div class="kt-card mt-4">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Uygunluk Testi</h3>
                    </div>
                    <div class="kt-card-body flex flex-col gap-3">
                        <input type="date" id="availabilityDate" class="kt-input">
                        <select id="availabilityBlocks" class="kt-input">
                            <option value="1">30 dk</option>
                            <option value="2">60 dk</option>
                            <option value="3">90 dk</option>
                            <option value="4">120 dk</option>
                        </select>
                        <button type="button" class="kt-btn kt-btn-success" id="btnCheckAvailability">Uygun Saatleri Getir</button>

                        <div id="availabilityList" class="flex flex-wrap gap-2"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
