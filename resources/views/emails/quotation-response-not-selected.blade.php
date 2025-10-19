<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización sobre su Cotización</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #1D3557 0%, #457B9D 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #457B9D;
        }
        .info-box h3 {
            margin-top: 0;
            color: #1D3557;
        }
        .details {
            margin: 15px 0;
        }
        .details strong {
            color: #1D3557;
        }
        .thank-you-box {
            background: #fff3e0;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ff9800;
        }
        .cta-button {
            display: inline-block;
            background: #1D3557;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
        .invitation-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .invitation-box p {
            margin: 5px 0;
            color: #1976d2;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Actualización de Cotización</h1>
        <p style="margin: 10px 0 0 0;">Tavira - Gestión de Conjuntos Residenciales</p>
    </div>

    <div class="content">
        <p><strong>Estimado/a {{ $provider->contact_name ?? $provider->name }},</strong></p>

        <p>Queremos agradecerle por el tiempo y esfuerzo dedicado a elaborar su propuesta para la siguiente solicitud de cotización:</p>

        <div class="info-box">
            <h3>{{ $quotationRequest->title }}</h3>

            <div class="details">
                <strong>Descripción:</strong>
                <p>{{ $quotationRequest->description }}</p>
            </div>

            <div class="details">
                <strong>Su propuesta:</strong>
                <p>Monto cotizado: <strong>{{ number_format($quotationResponse->quoted_amount, 0, ',', '.') }} COP</strong></p>
                @if($quotationResponse->estimated_days)
                <p>Tiempo estimado: {{ $quotationResponse->estimated_days }} días</p>
                @endif
            </div>
        </div>

        <p>Le informamos que, después de una cuidadosa evaluación de todas las propuestas recibidas, en esta ocasión se ha seleccionado una propuesta diferente que mejor se ajusta a las necesidades específicas del proyecto.</p>

        <div class="thank-you-box">
            <strong>🙏 Valoramos su participación</strong>
            <p style="margin: 10px 0 0 0;">
                Apreciamos enormemente su interés y profesionalismo al presentar su cotización. Su propuesta fue considerada con toda la seriedad que merece.
            </p>
        </div>

        <p>Esta decisión <strong>no refleja la calidad de sus servicios</strong>, sino que responde a criterios específicos de este proyecto en particular. Cada solicitud tiene características únicas y diferentes consideraciones.</p>

        <div class="invitation-box">
            <p>✨ <strong>¡Lo invitamos a seguir participando!</strong></p>
            <p style="margin: 10px 0; font-size: 14px; color: #555;">
                Valoramos mucho contar con proveedores profesionales como usted en nuestra plataforma.<br>
                Le animamos a estar atento a futuras solicitudes de cotización que puedan ser de su interés.
            </p>
        </div>

        <p>Estaremos publicando nuevas oportunidades regularmente, y esperamos contar con su participación en próximas ocasiones.</p>

        <center>
            <a href="{{ config('app.url') }}" class="cta-button">
                Ver Nuevas Oportunidades
            </a>
        </center>

        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            Gracias nuevamente por su confianza y por formar parte de nuestra red de proveedores de confianza.
        </p>

        <p style="margin-top: 20px;">
            Cordialmente,<br>
            <strong>El equipo de Tavira</strong>
        </p>
    </div>

    <div class="footer">
        <p>Este correo fue enviado automáticamente por Tavira</p>
        <p>© {{ date('Y') }} Tavira - Todos los derechos reservados</p>
    </div>
</body>
</html>
