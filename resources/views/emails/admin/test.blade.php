@php
    $siteName = $settings->site_name ?: config('app.name');
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Test</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; color:#152033; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px; overflow:hidden; border-radius:22px; background:#ffffff; box-shadow:0 18px 50px rgba(20, 35, 60, 0.12);">
                    <tr>
                        <td style="padding:28px 30px; background:#0f172a;">
                            <div style="font-size:13px; letter-spacing:0.08em; text-transform:uppercase; color:#93c5fd;">{{ $siteName }}</div>
                            <h1 style="margin:10px 0 0; color:#ffffff; font-size:26px; line-height:1.25;">SMTP ayarları çalışıyor</h1>
                            <p style="margin:10px 0 0; color:#cbd5e1; font-size:15px; line-height:1.6;">Bu test e-postası site ayarlarındaki SMTP bilgileriyle gönderildi.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 30px; font-size:16px; line-height:1.7;">
                            Panel mesajları ve randevu bildirimleri bu posta altyapısını kullanacak.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
