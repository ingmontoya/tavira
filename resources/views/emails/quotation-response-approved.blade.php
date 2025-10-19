<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Su Cotización Ha Sido Aprobada!</title>
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
            background: linear-gradient(135deg, #06D6A0 0%, #1D3557 100%);
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
        .success-box {
            background: #d4edda;
            border: 2px solid #06D6A0;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .success-box h2 {
            color: #155724;
            margin-top: 0;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #06D6A0;
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
        .amount-highlight {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #ffc107;
        }
        .amount-highlight .amount {
            font-size: 28px;
            font-weight: bold;
            color: #1D3557;
        }
        .cta-button {
            display: inline-block;
            background: #06D6A0;
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
        .next-steps {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .next-steps ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 ¡Felicitaciones!</h1>
        <p style="margin: 10px 0 0 0;">Su cotización ha sido aprobada</p>
    </div>

    <div class="content">
        <p><strong>Estimado/a {{ $provider->contact_name ?? $provider->name }},</strong></p>

        <div class="success-box">
            <h2>✅ ¡Excelentes noticias!</h2>
            <p style="margin: 10px 0; font-size: 16px;">
                Su propuesta ha sido seleccionada y aprobada
            </p>
        </div>

        <p>Nos complace informarle que su cotización para la siguiente solicitud ha sido aprobada:</p>

        <div class="info-box">
            <h3>{{ $quotationRequest->title }}</h3>

            <div class="details">
                <strong>Descripción del proyecto:</strong>
                <p>{{ $quotationRequest->description }}</p>
            </div>
        </div>

        <div class="amount-highlight">
            <p style="margin: 0; color: #666; font-size: 14px;">Monto Aprobado</p>
            <div class="amount">
                {{ number_format($quotationResponse->quoted_amount, 0, ',', '.') }} COP
            </div>
            @if($quotationResponse->estimated_days)
            <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                Tiempo estimado: {{ $quotationResponse->estimated_days }} días
            </p>
            @endif
        </div>

        <div class="next-steps">
            <strong>📋 Próximos pasos:</strong>
            <ul>
                <li>El conjunto residencial se pondrá en contacto con usted en breve para coordinar los detalles</li>
                <li>Por favor, tenga preparada la documentación necesaria para iniciar el proyecto</li>
                <li>Confirme su disponibilidad para comenzar según el cronograma acordado</li>
            </ul>
        </div>

        <p>Agradecemos su profesionalismo y la calidad de su propuesta. Estamos seguros de que este será el inicio de una excelente colaboración.</p>

        <center>
            <a href="{{ config('app.url') }}" class="cta-button">
                Acceder a Mi Panel
            </a>
        </center>

        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            Si tiene alguna pregunta, no dude en contactarnos.
        </p>
    </div>

    <div class="footer">
        <p>Este correo fue enviado automáticamente por Tavira</p>
        <p>© {{ date('Y') }} Tavira - Todos los derechos reservados</p>
    </div>
</body>
</html>
