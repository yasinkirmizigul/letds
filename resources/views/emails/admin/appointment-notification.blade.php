@php
    $siteName = $settings->site_name ?: config('app.name');
    $memberName = $appointment->member?->full_name ?: 'Üye';
    $startAt = $appointment->start_at?->copy()->timezone('Europe/Istanbul');
    $endAt = $appointment->end_at?->copy()->timezone('Europe/Istanbul');
    $duration = ((int) ($appointment->blocks ?? 1)) * 30;
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; color:#152033; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px; overflow:hidden; border-radius:22px; background:#ffffff; box-shadow:0 18px 50px rgba(20, 35, 60, 0.12);">
                    <tr>
                        <td style="padding:28px 30px; background:#111827;">
                            <span style="display:inline-block; padding:7px 11px; border-radius:999px; background:#22c55e; color:#052e16; font-size:12px; font-weight:700;">{{ $badge }}</span>
                            <h1 style="margin:14px 0 0; color:#ffffff; font-size:27px; line-height:1.25;">{{ $title }}</h1>
                            <p style="margin:10px 0 0; color:#d1d5db; font-size:15px; line-height:1.6;">{{ $intro }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 30px;">
                            <div style="margin-bottom:20px; color:#64748b; font-size:14px;">{{ $siteName }} panel bildirimi</div>

                            <div style="border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px; width:150px;">Üye</td>
                                        <td style="padding:18px 20px; font-weight:700;">{{ $memberName }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">E-posta</td>
                                        <td style="padding:18px 20px;">{{ $appointment->member?->email ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">Telefon</td>
                                        <td style="padding:18px 20px;">{{ $appointment->member?->phone ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">Panel kullanıcısı</td>
                                        <td style="padding:18px 20px;">{{ $appointment->provider?->name ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">Tarih</td>
                                        <td style="padding:18px 20px;">{{ $startAt?->format('d.m.Y') ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">Saat</td>
                                        <td style="padding:18px 20px;">{{ $startAt?->format('H:i') ?: '-' }} - {{ $endAt?->format('H:i') ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">Süre</td>
                                        <td style="padding:18px 20px;">{{ $duration }} dk</td>
                                    </tr>
                                </table>
                            </div>

                            @if(filled($appointment->notes_internal) || filled($appointment->cancel_reason))
                                <div style="margin-top:22px; padding:22px; border-radius:16px; background:#f8fafc; border:1px solid #e2e8f0;">
                                    @if(filled($appointment->cancel_reason))
                                        <div style="font-size:13px; color:#64748b; margin-bottom:8px;">İptal açıklaması</div>
                                        <div style="font-size:16px; line-height:1.7; color:#152033;">{{ $appointment->cancel_reason }}</div>
                                    @elseif(filled($appointment->notes_internal))
                                        <div style="font-size:13px; color:#64748b; margin-bottom:8px;">Panel notu</div>
                                        <div style="font-size:16px; line-height:1.7; color:#152033;">{!! nl2br(e($appointment->notes_internal)) !!}</div>
                                    @endif
                                </div>
                            @endif

                            <div style="padding-top:26px;">
                                <a href="{{ $calendarUrl }}" style="display:inline-block; border-radius:12px; background:#2563eb; color:#ffffff; font-size:15px; font-weight:700; padding:13px 18px; text-decoration:none;">Takvimi aç</a>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 30px; background:#f8fafc; color:#64748b; font-size:12px; line-height:1.6;">
                            Bu bildirim, site ayarlarındaki randevu e-posta bildirimleri açık olduğu için gönderildi.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
