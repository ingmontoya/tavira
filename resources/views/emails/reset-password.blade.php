<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Habitta</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            color: #2563eb;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .title {
            color: #1e40af;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background-color: #dc2626;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background-color: #b91c1c;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #92400e;
        }
        .link-info {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #0c4a6e;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Habitta</div>
            <h1 class="title">Restablecer Contraseña</h1>
        </div>

        <div class="content">
            <p>Hola,</p>
            
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Habitta. Si fuiste tú quien hizo esta solicitud, haz clic en el botón de abajo para crear una nueva contraseña:</p>

            <div style="text-align: center;">
                <a href="{{ $url }}" class="button">Restablecer Contraseña</a>
            </div>

            <div class="link-info">
                <strong>Importante:</strong> Este enlace expirará en {{ $count }} {{ $count == 1 ? 'minuto' : 'minutos' }}. Si no puedes hacer clic en el botón, copia y pega la siguiente URL en tu navegador:
                <br><br>
                <code style="word-break: break-all;">{{ $url }}</code>
            </div>

            <div class="warning">
                <strong>¿No solicitaste restablecer tu contraseña?</strong><br>
                Si no fuiste tú quien solicitó este cambio, puedes ignorar este correo. Tu contraseña actual permanecerá sin cambios y tu cuenta estará segura.
            </div>

            <p>Por motivos de seguridad, te recomendamos que:</p>
            <ul>
                <li>Uses una contraseña fuerte y única</li>
                <li>No compartas tu contraseña con nadie</li>
                <li>Cambies tu contraseña regularmente</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>Equipo de Seguridad Habitta</strong><br>
            La solución completa para la administración de conjuntos residenciales</p>
            
            <p style="margin-top: 20px; font-size: 12px;">
                Este es un correo automático, por favor no respondas a esta dirección.<br>
                Si necesitas ayuda, contacta a nuestro equipo de soporte.
            </p>
        </div>
    </div>
</body>
</html>