<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>Cuenta Suspendida - Tavira Seguridad</title>
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
            color: #ea580c;
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
        .suspension-box {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            color: white;
        }
        .suspension-box h3 {
            margin: 0 0 15px 0;
            font-size: 20px;
        }
        .suspension-box p {
            margin: 0;
            opacity: 0.9;
        }
        .suspension-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .reason-box {
            background-color: #fff7ed;
            border-left: 4px solid #ea580c;
            padding: 15px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }
        .reason-box h4 {
            color: #c2410c;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .reason-box p {
            color: #9a3412;
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
            .suspension-box {
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

        <h1 class="title">Cuenta Suspendida</h1>
        <p class="subtitle">Hola {{ $name }}, tu cuenta ha sido suspendida temporalmente</p>

        <div class="content">
            <div class="suspension-box">
                <div class="suspension-icon">&#9888;</div>
                <h3>Acceso Suspendido</h3>
                <p>Tu cuenta de Tavira Seguridad ha sido suspendida y no podras acceder a la aplicacion hasta que se resuelva el problema.</p>
            </div>

            <div class="reason-box">
                <h4>Motivo de la suspension</h4>
                <p>{{ $reason }}</p>
            </div>

            <div class="info-box">
                <h4>Que puedes hacer</h4>
                <p>Si crees que esta suspension es un error o deseas apelar esta decision, puedes contactar a nuestro equipo de soporte. Estaremos encantados de revisar tu caso.</p>
            </div>

            <p style="margin-top: 25px;"><strong>Tienes preguntas?</strong> Contacta a nuestro equipo de soporte para mas informacion sobre esta suspension.</p>
        </div>

        <div class="footer">
            <p><strong>Equipo Tavira Seguridad</strong><br>
            Sistema de respuesta a emergencias para conjuntos residenciales</p>

            <div class="footer-links">
                <a href="https://tavira.com.co">Sitio web</a>
                <a href="mailto:hola@tavira.com.co">Soporte</a>
            </div>

            <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
                Este correo fue enviado porque tu cuenta de Tavira Seguridad fue suspendida.
            </p>
        </div>
    </div>
    </div>
</body>
</html>
