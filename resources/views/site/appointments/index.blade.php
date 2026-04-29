@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto grid max-w-5xl gap-5 px-4 py-6 lg:px-8 lg:py-8">
        <section class="app-shell-surface rounded-[24px] p-5 lg:p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <div class="dashboard-kicker">Randevu</div>
                    <h1 class="mt-3 text-2xl font-semibold tracking-tight text-foreground lg:text-3xl">
                        Randevu Oluştur
                    </h1>
                </div>
                <div id="calendarTitle" class="text-lg font-semibold text-foreground"></div>
            </div>
        </section>

        @if($activeAppointment)
            <section class="app-surface-card app-surface-card--success rounded-[20px] p-5" id="active-appointment-card">
                <div class="text-sm font-semibold text-foreground">Aktif randevun var</div>
                <div class="mt-2 text-base font-medium text-foreground">
                    {{ $activeAppointment->start_at->format('d.m.Y H:i') }}
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
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

        <section id="booking-panel" class="app-surface-card rounded-[20px] p-5 lg:p-6 {{ $activeAppointment ? 'hidden' : '' }}">
            <div id="reschedule-mode-banner" class="mb-4 hidden rounded-xl border border-blue-500/30 bg-background/70 px-4 py-3 text-sm font-medium text-foreground">
                Yeniden planlama modundasın. Yeni tarih ve saat seç.
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr),auto] lg:items-end">
                <div>
                    <label class="kt-form-label mb-2">Kişi</label>
                    <select id="provider" class="kt-select w-full">
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="prevMonthBtn" class="kt-btn kt-btn-light">Önceki Ay</button>
                    <button type="button" id="nextMonthBtn" class="kt-btn kt-btn-light">Sonraki Ay</button>
                </div>
            </div>

            <div id="calendar" class="mt-5"></div>

            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,320px),1fr] lg:items-start">
                <div>
                    <label class="kt-form-label mb-2">Seçili tarih</label>
                    <div class="kt-input w-full">
                        <i class="ki-outline ki-calendar"></i>
                        <input
                            id="date"
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
                    <label class="kt-form-label mb-2">Uygun saatler</label>
                    <div id="slots" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"></div>

                    <div id="slot-empty" class="mt-3 hidden text-sm text-muted-foreground">
                        Bu gün için uygun saat bulunamadı.
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@vite('resources/js/site/appointments/index.js')
