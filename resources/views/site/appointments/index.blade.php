@extends('site.layouts.main.app')

@push('site_vendor_css')
    <link rel="stylesheet" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}">
@endpush

@push('site_vendor_js')
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
@endpush

@section('content')
    <div class="mx-auto grid max-w-5xl gap-5 px-4 py-6 lg:px-8 lg:py-8">
        <div>
            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Randevu</div>
            <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight lg:text-4xl">
                Randevu Oluştur
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">Kişiyi seç, uygun bir gün ve saat belirle — hepsi bu.</p>
        </div>

        @if($activeAppointment)
            <section class="rounded-3xl border border-success/30 bg-success/5 p-6" id="active-appointment-card">
                <div class="text-sm font-semibold text-foreground">Aktif randevun var</div>
                <div class="mt-2 font-display text-2xl text-foreground">
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

        <section id="booking-panel" class="rounded-3xl border border-border bg-background p-5 lg:p-8 {{ $activeAppointment ? 'hidden' : '' }}">
            <div id="reschedule-mode-banner" class="mb-4 hidden rounded-xl border border-blue-500/30 bg-background/70 px-4 py-3 text-sm font-medium text-foreground">
                Yeniden planlama modundasın. Yeni tarih ve saat seç.
            </div>

            <div class="grid gap-8">
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">1</span>
                        <span class="text-sm font-semibold text-foreground">Kişi</span>
                    </div>

                    <div>
                        <label class="kt-form-label mb-2">Kişi</label>
                        <select id="provider" class="kt-select w-full">
                            @foreach($providers as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">2</span>
                        <span class="text-sm font-semibold text-foreground">Gün</span>
                    </div>

                    <div class="mb-3 flex items-center justify-between gap-2">
                        <button type="button" id="prevMonthBtn" class="kt-btn kt-btn-outline" aria-label="Önceki ay">
                            <i class="ki-outline ki-left"></i>
                        </button>
                        <div id="calendarTitle" class="px-3 text-sm font-semibold text-foreground"></div>
                        <button type="button" id="nextMonthBtn" class="kt-btn kt-btn-outline" aria-label="Sonraki ay">
                            <i class="ki-outline ki-right"></i>
                        </button>
                    </div>
                    <div id="calendar"></div>
                </div>

                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">3</span>
                        <span class="text-sm font-semibold text-foreground">Saat</span>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-[minmax(0,320px),1fr] lg:items-start">
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
                                    data-app-date-format="DD.MM.YYYY"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="kt-form-label mb-2">Uygun saatler</label>
                            <div id="slots" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"></div>

                            <div id="slot-empty" class="mt-3 hidden text-sm text-muted-foreground">
                                Bu gün için uygun saat bulunamadı. Başka bir gün seçmeyi dene.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('site_js')
    @vite('resources/js/site/appointments/index.js')
@endpush
