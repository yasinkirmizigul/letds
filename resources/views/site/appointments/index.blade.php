@extends('site.layouts.main.app')

@section('content')
    <div class="container py-5">
        <h3 class="mb-4">Randevu Al</h3>

        @if($activeAppointment)
            <div class="p-4 border rounded bg-green-50 mb-4" id="active-appointment-card">
                <strong>Aktif randevun var</strong><br>
                <span class="d-block mt-1">
                    {{ $activeAppointment->start_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}
                </span>

                <div class="mt-3 flex gap-2">
                    <button id="cancelBtn" class="btn btn-danger">İptal Et</button>
                    <button id="rescheduleBtn" class="btn btn-primary">Yeniden Planla</button>
                </div>
            </div>

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

        <div id="booking-panel" class="{{ $activeAppointment ? 'hidden' : '' }}">
            <div id="reschedule-mode-banner" class="hidden p-3 border rounded bg-blue-50 text-sm mb-3">
                Yeniden planlama modundasın. Yeni tarih ve saat seç.
            </div>

            <select id="provider" class="form-control mb-3">
                @foreach($providers as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>

            <div class="flex items-center justify-between mb-3">
                <button type="button" id="prevMonthBtn" class="btn btn-light">Önceki Ay</button>
                <div id="calendarTitle" class="font-semibold"></div>
                <button type="button" id="nextMonthBtn" class="btn btn-light">Sonraki Ay</button>
            </div>

            <div id="calendar" class="mb-4"></div>

            <input type="date" id="date" class="form-control mb-3">

            <div id="slots" class="grid grid-cols-3 md:grid-cols-5 gap-3"></div>

            <div id="slot-empty" class="text-sm text-gray-500 mt-3 hidden">
                Bu gün için uygun saat bulunamadı.
            </div>
        </div>
    </div>
@endsection

@vite('resources/js/site/appointments/index.js')
