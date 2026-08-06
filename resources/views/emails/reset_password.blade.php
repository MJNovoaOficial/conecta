<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperación de contraseña</title>
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:40px 0;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
      <!-- Header -->
      <tr>
        <td style="background:linear-gradient(135deg,#1a2332 0%,#2d4a6e 100%);padding:32px 40px;text-align:center;">
          <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.3px;">
            🔐 Conecta Mesa de Ayuda
          </h1>
          <p style="margin:6px 0 0;color:rgba(255,255,255,0.7);font-size:13px;">Sistema de Soporte Dimak</p>
        </td>
      </tr>
      <!-- Content -->
      <tr>
        <td style="padding:36px 40px;">
          <p style="margin:0 0 16px;color:#1a2332;font-size:16px;font-weight:600;">Hola, {{ $user->name }}.</p>
          <p style="margin:0 0 20px;color:#4a5568;font-size:14px;line-height:1.7;">
            Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>Conecta Soporte</strong>.
            Si no realizaste esta solicitud, puedes ignorar este correo.
          </p>
          <p style="margin:0 0 28px;color:#4a5568;font-size:14px;line-height:1.7;">
            Este enlace es válido por <strong>60 minutos</strong>. Haz clic en el botón para crear una nueva contraseña:
          </p>
          <!-- CTA Button -->
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td align="center" style="padding:8px 0 28px;">
                <a href="{{ $resetUrl }}"
                   style="display:inline-block;background:linear-gradient(135deg,#4f8cff,#2563eb);color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:14px 36px;border-radius:8px;letter-spacing:0.2px;">
                  Restablecer mi contraseña
                </a>
              </td>
            </tr>
          </table>
          <p style="margin:0 0 12px;color:#718096;font-size:12px;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
          <p style="margin:0 0 28px;word-break:break-all;">
            <a href="{{ $resetUrl }}" style="color:#4f8cff;font-size:12px;">{{ $resetUrl }}</a>
          </p>
          <hr style="border:none;border-top:1px solid #e8ecf0;margin:0 0 24px;">
          <p style="margin:0;color:#a0aec0;font-size:12px;line-height:1.6;">
            Este correo fue enviado automáticamente por Conecta Soporte.<br>
            Por seguridad, nunca compartas este enlace con nadie.
          </p>
        </td>
      </tr>
      <!-- Footer -->
      <tr>
        <td style="background:#f7f9fc;padding:20px 40px;text-align:center;border-top:1px solid #e8ecf0;">
          <p style="margin:0;color:#a0aec0;font-size:11px;">© {{ date("Y") }} Dimak — Sistema de Soporte Interno</p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
