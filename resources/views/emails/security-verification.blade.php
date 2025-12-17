<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>Verificar correo electrónico - Tavira Seguridad</title>
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
            border-radius: 12px;
            padding: 40px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            width: 100%;
            box-sizing: border-box;
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
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
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
            color: #dc2626;
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
        .info-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }
        .info-box h4 {
            color: #1e40af;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .info-box p {
            color: #1e3a8a;
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
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px 0 !important;
            }
            .container {
                padding: 20px 15px !important;
                border-radius: 0 !important;
                margin: 0 10px !important;
            }
            .title {
                font-size: 24px !important;
            }
            .verify-button {
                padding: 14px 28px !important;
                font-size: 15px !important;
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .verification-box {
                margin: 20px 0 !important;
                padding: 20px !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="https://tavira.com.co/img/tavira_logo_blanco.png" alt="Tavira - Plataforma de Seguridad" class="logo-image" style="max-height: 100px; width: auto; margin: 0 auto; display: block; border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic;" border="0">
            </div>
        </div>

        <h1 class="title">Verificar correo electrónico</h1>
        <p class="subtitle">Hola {{ $name }}, confirma tu correo para completar tu registro como personal de seguridad</p>

        <div class="content">
            <div class="verification-box">
                <h3>Verificacion de correo requerida</h3>
                <p>Para continuar con tu registro en Tavira Seguridad, necesitamos verificar que esta direccion de correo te pertenece.</p>

                <a href="{{ $verificationUrl }}" class="verify-button">
                    Verificar mi correo electronico
                </a>
            </div>

            <div class="info-box">
                <h4>Proceso de registro</h4>
                <p>Despues de verificar tu correo, tu solicitud sera revisada por un administrador. Recibiras una notificacion cuando tu cuenta sea aprobada.</p>
            </div>

            <div class="security-notice">
                <h4>Nota de seguridad</h4>
                <p>Si no solicitaste registrarte en Tavira Seguridad, puedes ignorar este correo de forma segura. Tu direccion de correo no sera utilizada sin tu verificacion.</p>
            </div>

            <p><strong>Pasos del registro:</strong></p>
            <ol style="color: #374151; line-height: 1.8;">
                <li>Verifica tu correo electronico (paso actual)</li>
                <li>Un administrador revisara tu solicitud</li>
                <li>Recibiras una notificacion de aprobacion</li>
                <li>Podras iniciar sesion y responder a alertas</li>
            </ol>

            <p style="margin-top: 25px;"><strong>Necesitas ayuda?</strong> Contacta a nuestro equipo de soporte.</p>
        </div>

        <div class="footer">
            <p><strong>Equipo Tavira Seguridad</strong><br>
            Sistema de respuesta a emergencias para conjuntos residenciales</p>

            <div class="footer-links">
                <a href="https://tavira.com.co">Sitio web</a>
                <a href="mailto:hola@tavira.com.co">Soporte</a>
            </div>

            <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
                Este enlace expira en 24 horas por seguridad. Si el boton no funciona, copia y pega esta URL en tu navegador:<br>
                <span style="word-break: break-all; color: #6b7280;">{{ $verificationUrl }}</span>
            </p>
        </div>
    </div>
    </div>
</body>
</html>
