@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Metronic / Bootstrap grid + card yapısı --}}
    <div class="container-xxl" id="kt_content_container">
        <div class="row g-5 g-xl-10">

            <div class="col-xl-4 mb-5 mb-xl-10">
                <div class="card card-xl-stretch">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-dark">Test Kartı</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">
                                Metronic stilinin gerçekten çalışıp çalışmadığını görüyorsun
                            </span>
                        </h3>
                    </div>
                    <div class="card-body py-3">
                        <p class="fs-6 text-gray-700 mb-4">
                            Eğer burada mavi başlık, gri açıklama, kart gölgesi vs. görüyorsan
                            Metronic CSS düzgün yüklenmiş demektir.
                        </p>

                        <a href="#" class="btn btn-primary">
                            <i class="ki-outline ki-rocket fs-4 me-2"></i>
                            Bootstrap / Metronic Butonu
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 mb-5 mb-xl-10">
                <div class="card card-xl-stretch">
                    <div class="card-body py-5">
                        <h4 class="mb-3">Grid ve spacing testi</h4>
                        <p class="mb-2">
                            Bu alanı sadece stilin oturduğunu görmek için kullanıyoruz.
                        </p>
                        <ul class="mb-0">
                            <li>Yazı fontu default Laravel değil, Metronic fontu olmalı.</li>
                            <li>Card’ın köşeleri radius’lu ve gölgeli olmalı.</li>
                            <li>Buton gerçekten “Metronic butonu” gibi görünmeli.</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Dashboard init ► Metronic kartları yüklendi');
        });
    </script>
@endpush
