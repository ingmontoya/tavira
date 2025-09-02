<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Tavira</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            width: 100% !important;
            min-width: 100%;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            padding: 40px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            box-sizing: border-box;
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
        .welcome-title {
            color: #1e40af;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .features {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .features ul {
            margin: 0;
            padding-left: 20px;
        }
        .features li {
            margin: 10px 0;
            color: #374151;
        }
        @media only screen and (max-width: 600px) {
            .container {
                padding: 20px 15px !important;
                border-radius: 0 !important;
            }
            .welcome-title {
                font-size: 20px !important;
            }
            .button {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="{{ asset('public/img/tavira_logo_blanco.svg') }}" alt="Tavira Logo" class="logo-image" style="max-height: 60px; width: auto; margin-bottom: 10px; display: block;">
            </div>
            <h1 class="welcome-title">¡Bienvenido a Tavira!</h1>
        </div>

        <div class="content">
            <p>Hola <strong>{{ $user->name }}</strong>,</p>

            <p>¡Tu cuenta ha sido creada exitosamente! Ahora tienes acceso a la plataforma integral de administración de propiedades residenciales más completa de Colombia.</p>

            <div class="features">
                <h3 style="margin-top: 0; color: #1f2937;">¿Qué puedes hacer en Tavira?</h3>
                <ul>
                    <li><strong>Gestión de Residentes:</strong> Administra la información de todos los residentes y apartamentos</li>
                    <li><strong>Control Financiero:</strong> Maneja pagos, facturas y acuerdos de pago de manera eficiente</li>
                    <li><strong>Reportes Detallados:</strong> Genera reportes completos sobre el estado del conjunto</li>
                    <li><strong>Comunicación:</strong> Envía correspondencia y anuncios a los residentes</li>
                    <li><strong>Seguridad:</strong> Control de acceso y gestión de visitantes</li>
                </ul>
            </div>

            <p style="text-align: center;">
                <a href="{{ route('dashboard') }}" class="button">Acceder al Dashboard</a>
            </p>

            <p>Si tienes alguna pregunta o necesitas ayuda para comenzar, no dudes en contactar a nuestro equipo de soporte.</p>

            <p>¡Gracias por confiar en Tavira para la administración de tu conjunto residencial!</p>
        </div>

        <div class="footer">
            <p><strong>Equipo Tavira</strong><br>
            La solución completa para la administración de conjuntos residenciales</p>

            <p style="margin-top: 20px; font-size: 12px;">
                Este es un correo automático, por favor no respondas a esta dirección.
            </p>
        </div>
    </div>
    </div>
</body>
</html>
