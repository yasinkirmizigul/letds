@php
    $siteName = $settings->site_name ?: config('app.name');
    $senderName = $contactMessage->sender_full_name ?: 'Bilinmeyen gönderici';
    $priorityLabel = \App\Models\ContactMessage::priorityLabel($contactMessage->priority);
    $priorityColor = match ($contactMessage->priority) {
        \App\Models\ContactMessage::PRIORITY_URGENT => '#dc2626',
        \App\Models\ContactMessage::PRIORITY_HIGH => '#d97706',
        \App\Models\ContactMessage::PRIORITY_LOW => '#64748b',
        default => '#2563eb',
    };
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Mesaj</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; color:#152033; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px; overflow:hidden; border-radius:22px; background:#ffffff; box-shadow:0 18px 50px rgba(20, 35, 60, 0.12);">
                    <tr>
                        <td style="padding:28px 30px; background:#0f172a;">
                            <div style="font-size:13px; letter-spacing:0.08em; text-transform:uppercase; color:#93c5fd;">{{ $siteName }}</div>
                            <h1 style="margin:10px 0 0; color:#ffffff; font-size:26px; line-height:1.25;">Yeni mesaj alındı</h1>
                            <p style="margin:10px 0 0; color:#cbd5e1; font-size:15px; line-height:1.6;">Panel hesabınıza yönlendirilen yeni bir iletişim mesajı var.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding:0 0 18px;">
                                        <span style="display:inline-block; padding:7px 11px; border-radius:999px; background:{{ $priorityColor }}; color:#ffffff; font-size:12px; font-weight:700;">{{ $priorityLabel }}</span>
                                    </td>
                                </tr>
                            </table>

                            <div style="border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px; width:150px;">Konu</td>
                                        <td style="padding:18px 20px; font-weight:700;">{{ $contactMessage->subject }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">Gönderen</td>
                                        <td style="padding:18px 20px;">{{ $senderName }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">E-posta</td>
                                        <td style="padding:18px 20px;">{{ $contactMessage->sender_email ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">Telefon</td>
                                        <td style="padding:18px 20px;">{{ $contactMessage->sender_phone ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 20px; background:#f8fafc; color:#64748b; font-size:13px;">Kanal</td>
                                        <td style="padding:18px 20px;">{{ $contactMessage->preferredChannelsLabel() }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div style="margin-top:22px; padding:22px; border-radius:16px; background:#f8fafc; border:1px solid #e2e8f0;">
                                <div style="font-size:13px; color:#64748b; margin-bottom:8px;">Mesaj</div>
                                <div style="font-size:16px; line-height:1.7; color:#152033;">{!! nl2br(e($contactMessage->message)) !!}</div>
                            </div>

                            <div style="padding-top:26px;">
                                <a href="{{ $messageUrl }}" style="display:inline-block; border-radius:12px; background:#2563eb; color:#ffffff; font-size:15px; font-weight:700; padding:13px 18px; text-decoration:none;">Panelde aç</a>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 30px; background:#f8fafc; color:#64748b; font-size:12px; line-height:1.6;">
                            Bu bildirim, site ayarlarındaki e-posta bildirimleri açık olduğu için gönderildi.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
