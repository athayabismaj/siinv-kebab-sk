<!DOCTYPE html>
<html>
    <body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
        <tr>
        <td align="center">
            <table width="500" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;padding:30px;">
        <tr>
        <td align="center" style="padding-bottom:20px;">
            <h2 style="margin:0;color:#111827;">
                Sistem Inventory Kebab SK
            </h2>
        </td>
        </tr>
        <tr>
        <td style="font-size:15px;color:#374151;">
            Halo,
            <br><br>
            Kami menerima permintaan untuk mereset password akun Anda.
            Gunakan kode OTP berikut:
        </td>
        </tr>
        <tr>
        <td align="center" style="padding:25px 0;">
            <div style="display:inline-block; background:#2563eb; color:white; font-size:32px; font-weight:bold; letter-spacing:6px; padding:15px 30px;
                border-radius:8px;">
                {{ $otp }}
            </div>
        </td>
        </tr>
        <tr>
        <td style="font-size:14px;color:#6b7280;">
            OTP berlaku selama <strong>5 menit</strong>.
            <br>
            Jika Anda tidak meminta reset password, abaikan email ini.
        </td>
        </tr>
        <tr>
        <td style="padding-top:30px;font-size:12px;color:#9ca3af;text-align:center;">
            © {{ date('Y') }} Sistem Inventory Kebab SK
        </td>
        </tr>
            </table>
        </td>
        </tr>
            </table>
    </body>
</html>