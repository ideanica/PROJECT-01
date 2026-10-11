<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur ulang password Ocean Paws</title>
</head>
<body style="margin:0;padding:0;background-color:#fbfaf4;font-family:Arial,Helvetica,sans-serif;color:#355a45;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">Atur password baru untuk akun Ocean Paws. Tautan berlaku {{ $expiresInMinutes }} menit.</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#fbfaf4;">
        <tr><td align="center" style="padding:32px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;border:2px solid #355a45;border-radius:16px;background-color:#ffffff;">
                <tr><td style="padding:24px;border-bottom:2px solid #355a45;background-color:#ffd7e3;border-radius:14px 14px 0 0;">
                    <p style="margin:0;font-size:24px;font-weight:bold;color:#355a45;">Ocean Paws</p>
                    <p style="margin:6px 0 0;font-size:12px;letter-spacing:1px;color:#805064;">KEAMANAN AKUN</p>
                </td></tr>
                <tr><td style="padding:28px 24px;">
                    <h1 style="margin:0 0 20px;font-size:26px;line-height:1.3;color:#355a45;">Atur ulang password</h1>
                    <p style="margin:0 0 12px;font-size:16px;line-height:1.6;">Halo {{ $customerName }},</p>
                    <p style="margin:0 0 24px;font-size:15px;line-height:1.7;">Kami menerima permintaan untuk mengatur ulang password akun Ocean Paws kamu. Klik tombol di bawah untuk membuat password baru.</p>
                    <table role="presentation" cellspacing="0" cellpadding="0"><tr><td bgcolor="#355a45" style="border-radius:10px;">
                        <a href="{{ $resetUrl }}" style="display:inline-block;padding:16px 24px;border:2px solid #355a45;border-radius:10px;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;">Atur ulang password</a>
                    </td></tr></table>
                    <p style="margin:20px 0;font-size:14px;line-height:1.7;">Tautan ini berlaku selama <strong>{{ $expiresInMinutes }} menit</strong> dan hanya dapat digunakan sekali. Jika sudah kedaluwarsa, ajukan permintaan reset password kembali dari halaman login.</p>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="padding:16px;border:1px solid #8aaf91;border-radius:10px;background-color:#edf5ee;">
                        <p style="margin:0;font-size:14px;line-height:1.7;"><strong>Tidak meminta reset password?</strong><br>Abaikan email ini. Password kamu tetap sama selama kamu tidak membuat password baru. Jangan bagikan tautan ini kepada siapa pun.</p>
                    </td></tr></table>
                    <p style="margin:24px 0 8px;font-size:13px;line-height:1.6;color:#59675f;">Jika tombol tidak dapat dibuka, salin dan tempel tautan berikut ke browser:</p>
                    <p style="margin:0;font-size:12px;line-height:1.7;word-break:break-all;overflow-wrap:anywhere;"><a href="{{ $resetUrl }}" style="color:#355a45;text-decoration:underline;">{{ $resetUrl }}</a></p>
                </td></tr>
                <tr><td style="padding:20px 24px;border-top:1px solid #e3e9e3;font-size:12px;line-height:1.7;color:#59675f;">Salam,<br><strong>Tim Ocean Paws</strong><br>Email ini dikirim otomatis untuk membantu menjaga keamanan akunmu.</td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
