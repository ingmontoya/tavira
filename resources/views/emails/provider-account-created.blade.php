<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Tavira</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-top: none;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background: #059669;
        }
        .details {
            background: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .features {
            margin: 20px 0;
        }
        .feature {
            margin: 10px 0;
            padding-left: 25px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 28px;">¡Bienvenido a Tavira!</h1>
        <p style="margin: 10px 0 0 0; font-size: 16px;">Tu cuenta de proveedor ha sido creada</p>
    </div>

    <div class="content">
        <p>Hola <strong>{{ $contactName }}</strong>,</p>

        <p>Tu solicitud de registro como proveedor ha sido <strong>aprobada</strong> y tu cuenta ha sido creada exitosamente.</p>

        <div class="details">
            <h3 style="margin-top: 0;">Detalles de tu cuenta:</h3>
            <p style="margin: 5px 0;"><strong>Empresa:</strong> {{ $providerName }}</p>
            <p style="margin: 5px 0;"><strong>Email:</strong> {{ $user->email }}</p>
        </div>

        <h3>Configura tu contraseña</h3>
        <p>Para acceder a tu cuenta y comenzar a gestionar tu catálogo de servicios y responder solicitudes de cotización, necesitas configurar tu contraseña.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $setupUrl }}" class="button">Configurar mi contraseña</a>
        </div>

        <p style="font-size: 14px; color: #666;"><em>Este enlace es válido por <strong>60 minutos</strong>. Si el enlace expira, puedes solicitar uno nuevo en la página de inicio de sesión usando la opción "¿Olvidaste tu contraseña?".</em></p>

        <div class="features">
            <h3>¿Qué puedes hacer con tu cuenta?</h3>
            <div class="feature">✅ <strong>Gestionar tu catálogo de servicios</strong> - Agrega y administra los servicios que ofreces</div>
            <div class="feature">✅ <strong>Recibir solicitudes de cotización</strong> - Los conjuntos residenciales te enviarán solicitudes</div>
            <div class="feature">✅ <strong>Enviar propuestas</strong> - Responde a las solicitudes con tus mejores ofertas</div>
            <div class="feature">✅ <strong>Ver tu historial</strong> - Consulta todas tus propuestas y su estado</div>
        </div>

        <h3>¿Necesitas ayuda?</h3>
        <p>Si tienes alguna pregunta o problema configurando tu cuenta, no dudes en contactarnos.</p>

        <div class="footer">
            <p><strong>Nota:</strong> Si no solicitaste este registro, por favor ignora este correo. Tu seguridad es importante para nosotros.</p>
            <p>Gracias por unirte a Tavira,<br>{{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
