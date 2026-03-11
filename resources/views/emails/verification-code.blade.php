<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Codigo de verificacion</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 8px;">Verificacion de codigo</h2>
    <p style="margin-top: 0;">Usa este codigo para completar tu validacion:</p>

    <div style="font-size: 32px; font-weight: 700; letter-spacing: 4px; margin: 16px 0;">
        {{ $code }}
    </div>

    <p>Este codigo expira en {{ $expiresInMinutes }} minutos.</p>
    <p style="font-size: 12px; color: #6b7280;">Si no solicitaste este correo, puedes ignorarlo.</p>
</body>
</html>
