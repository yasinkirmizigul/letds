<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Randevu Bilgilendirmesi</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222;">
<h2>
    @if($type === 'transferred')
        Randevunuzun saati güncellendi
    @elseif($type === 'resized')
        Randevunuzun süresi güncellendi
    @elseif($type === 'cancelled')
        Randevunuz iptal edildi
    @else
        Randevu bilgilendirmesi
    @endif
</h2>

<p>Merhaba {{ $appointment->member?->full_name ?? 'Üye' }},</p>

<p>
    @if($type === 'transferred')
        Randevunuz yeni tarih/saat bilgisine göre güncellenmiştir.
    @elseif($type === 'resized')
        Randevunuzun süresi güncellenmiştir.
    @elseif($type === 'cancelled')
        Randevunuz hizmet veren kişi tarafından iptal edilmiştir.
    @endif
</p>

<ul>
    <li><strong>Kişi:</strong> {{ $appointment->provider?->name }}</li>
    <li><strong>Ünvan:</strong> {{ $appointment->provider?->title }}</li>
    <li><strong>Tarih:</strong> {{ optional($appointment->start_at)->format('d.m.Y') }}</li>
    <li><strong>Saat:</strong> {{ optional($appointment->start_at)->format('H:i') }} - {{ optional($appointment->end_at)->format('H:i') }}</li>
    <li><strong>Süre:</strong> {{ ($appointment->blocks ?? 1) * 30 }} dk</li>
    <li><strong>Durum:</strong> {{ $appointment->status }}</li>
</ul>

@if(!empty($appointment->cancel_reason))
    <p><strong>Açıklama:</strong> {{ $appointment->cancel_reason }}</p>
@endif

<p>Bilginize sunarız.</p>
</body>
</html>
