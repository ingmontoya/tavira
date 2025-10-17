<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Solicitud de Cotización</title>
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
        .categories {
            background: #e7f3ff;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .category-tag {
            display: inline-block;
            background: #1D3557;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            margin: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎯 Nueva Solicitud de Cotización</h1>
        <p style="margin: 10px 0 0 0;">Tavira - Gestión de Conjuntos Residenciales</p>
    </div>

    <div class="content">
        <p><strong>Hola, {{ $provider->contact_name ?? $provider->name }}!</strong></p>

        <p>Te informamos que se ha publicado una nueva solicitud de cotización que podría interesarte:</p>

        <div class="info-box">
            <h3>{{ $quotationRequest->title }}</h3>

            <div class="details">
                <strong>Descripción:</strong>
                <p>{{ $quotationRequest->description }}</p>
            </div>

            @if($quotationRequest->requirements)
            <div class="details">
                <strong>Requisitos adicionales:</strong>
                <p>{{ $quotationRequest->requirements }}</p>
            </div>
            @endif

            @if($quotationRequest->deadline)
            <div class="details">
                <strong>Fecha límite para responder:</strong>
                <p>{{ $quotationRequest->deadline->format('d/m/Y') }}</p>
            </div>
            @endif

            @if($quotationRequest->categories && $quotationRequest->categories->count() > 0)
            <div class="details">
                <strong>Categorías:</strong>
                <div class="categories">
                    @foreach($quotationRequest->categories as $category)
                        <span class="category-tag">{{ $category->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <p><strong>¿Cómo responder?</strong></p>
        <p>Para enviar tu propuesta y cotización, ingresa a tu panel de proveedor en Tavira y navega a la sección de solicitudes de cotización.</p>

        <center>
            <a href="{{ config('app.url') }}" class="cta-button">
                Acceder a Mi Panel
            </a>
        </center>

        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            <strong>Nota:</strong> Asegúrate de enviar tu propuesta antes de la fecha límite para ser considerado.
        </p>
    </div>

    <div class="footer">
        <p>Este correo fue enviado automáticamente por Tavira</p>
        <p>© {{ date('Y') }} Tavira - Todos los derechos reservados</p>
    </div>
</body>
</html>
