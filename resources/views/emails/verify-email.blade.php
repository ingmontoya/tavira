<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar correo electrónico - Tavira</title>
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
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-container {
            margin-bottom: 20px;
        }
        .logo-image {
            max-height: 60px;
            width: auto;
            margin-bottom: 10px;
        }
        .title {
            color: #1e40af;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 30px;
            text-align: center;
        }
        .content {
            margin-bottom: 30px;
        }
        .verification-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            color: white;
        }
        .verification-box h3 {
            margin: 0 0 15px 0;
            font-size: 20px;
        }
        .verification-box p {
            margin: 0 0 25px 0;
            opacity: 0.9;
        }
        .verify-button {
            display: inline-block;
            background-color: #ffffff;
            color: #667eea;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .security-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }
        .security-notice h4 {
            color: #92400e;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .security-notice p {
            color: #78350f;
            margin: 0;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .footer-links {
            margin-top: 15px;
        }
        .footer-links a {
            color: #2563eb;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
            }
            .title {
                font-size: 24px;
            }
            .verify-button {
                padding: 14px 28px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="{{ asset('img/tavira_logo_blanco.svg') }}" alt="Tavira Logo" class="logo-image">
            </div>
        </div>

        <h1 class="title">Verificar correo electrónico</h1>
        <p class="subtitle">Hola {{ $user->name }}, confirma tu dirección de correo para activar tu cuenta</p>

        <div class="content">
            <div class="verification-box">
                <h3>🔐 Verificación requerida</h3>
                <p>Para completar tu registro en Tavira, necesitamos verificar que esta dirección de correo te pertenece.</p>

                <a href="{{ $verificationUrl }}" class="verify-button">
                    ✓ Verificar mi correo electrónico
                </a>
            </div>

            <div class="security-notice">
                <h4>🛡️ Nota de seguridad</h4>
                <p>Si no creaste una cuenta en Tavira, puedes ignorar este correo de forma segura. Tu dirección de correo no será utilizada sin tu verificación.</p>
            </div>

            <p><strong>¿Qué sigue después de verificar?</strong></p>
            <ul style="color: #374151; line-height: 1.8;">
                <li>Accederás al dashboard de administración completo</li>
                <li>Podrás configurar tu conjunto residencial</li>
                <li>Comenzarás a gestionar residentes y apartamentos</li>
                <li>Tendrás acceso a todas las funcionalidades de Tavira</li>
            </ul>

            <p style="margin-top: 25px;"><strong>¿Necesitas ayuda?</strong> Nuestro equipo está listo para asistirte en el proceso de configuración inicial.</p>
        </div>

        <div class="footer">
            <p><strong>Equipo Tavira</strong><br>
            La solución completa para la administración de conjuntos residenciales</p>

            <div class="footer-links">
                <a href="https://tavira.com.co">Sitio web</a>
                <a href="mailto:soporte@tavira.com.co">Soporte</a>
            </div>

            <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
                Este enlace expira en 60 minutos por seguridad. Si el botón no funciona, copia y pega esta URL en tu navegador:<br>
                <span style="word-break: break-all; color: #6b7280;">{{ $verificationUrl }}</span>
            </p>
        </div>
    </div>
</body>
</html>
