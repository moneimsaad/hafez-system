<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $purpose === 'registration' ? 'تفعيل الحساب' : 'إعادة تعيين كلمة المرور' }}</title>
</head>

<body style="margin:0;padding:0;background:#f2f7f4;color:#173b33;font-family:Tahoma,Arial,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        {{ $purpose === 'registration' ? 'رمز تفعيل حسابك في '.$brandName : 'رمز إعادة تعيين كلمة المرور في '.$brandName }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f2f7f4;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #d8e7e0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:30px 32px 24px;background:#0f7665;color:#ffffff;text-align:right;">
                            <p style="margin:0 0 7px;font-size:13px;font-weight:700;letter-spacing:.2px;">{{ $brandName }}</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.5;font-weight:700;">{{ $purpose === 'registration' ? 'تفعيل حسابك' : 'إعادة تعيين كلمة المرور' }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px 8px;text-align:right;">
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.9;color:#24483f;">{{ $purpose === 'registration' ? 'استخدم الرمز التالي لإتمام إنشاء حسابك في '.$brandName.'.' : 'استخدم الرمز التالي للمتابعة إلى تعيين كلمة مرور جديدة.' }}</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;">
                                <tr>
                                    <td dir="ltr" align="center" style="padding:19px 16px;background:#edf8f3;border:1px solid #cce8da;border-radius:12px;color:#0f7665;font-family:Consolas,Monaco,monospace;font-size:34px;line-height:1.25;font-weight:800;letter-spacing:10px;">{{ $code }}</td>
                                </tr>
                            </table>
                            <p style="margin:17px 0 0;color:#4c6b62;font-size:14px;line-height:1.9;">تنتهي صلاحية هذا الرمز خلال {{ $expiresMinutes }} دقائق، ويمكن استخدامه مرة واحدة فقط.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px 30px;text-align:right;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-top:1px solid #e3eee9;">
                                <tr>
                                    <td style="padding-top:18px;color:#647d74;font-size:13px;line-height:1.9;">إذا لم تطلب هذا الإجراء، يمكنك تجاهل هذه الرسالة. لا تشارك رمز التحقق مع أي شخص.</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;background:#f8fbf9;color:#789088;text-align:center;font-size:12px;line-height:1.8;">هذه رسالة آلية من {{ $brandName }}.<br>للمساعدة، تواصل مع إدارة المنصة.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
