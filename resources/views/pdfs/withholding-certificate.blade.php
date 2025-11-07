<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Retención en la Fuente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            padding: 0;
        }
        .header h2 {
            font-size: 14px;
            margin: 5px 0;
            font-weight: normal;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 180px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        .totals table {
            margin: 0;
        }
        .signature-section {
            margin-top: 80px;
            clear: both;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 250px;
            text-align: center;
            padding-top: 5px;
        }
        .footer {
            margin-top: 30px;
            font-size: 9px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CERTIFICADO DE RETENCIÓN EN LA FUENTE</h1>
        <h2>Año Gravable {{ $certificate->year }}</h2>
        <p><strong>Certificado No:</strong> {{ $certificate->certificate_number }}</p>
    </div>

    <div class="info-section">
        <h3>INFORMACIÓN DEL AGENTE RETENEDOR</h3>
        <div class="info-row">
            <span class="label">Conjunto Residencial:</span>
            {{ $certificate->conjuntoConfig->name }}
        </div>
        <div class="info-row">
            <span class="label">NIT:</span>
            {{ $certificate->conjuntoConfig->nit ?? 'N/A' }}
        </div>
        <div class="info-row">
            <span class="label">Dirección:</span>
            {{ $certificate->conjuntoConfig->address ?? 'N/A' }}
        </div>
    </div>

    <div class="info-section">
        <h3>INFORMACIÓN DEL PROVEEDOR</h3>
        <div class="info-row">
            <span class="label">Nombre o Razón Social:</span>
            {{ $certificate->provider->name }}
        </div>
        <div class="info-row">
            <span class="label">NIT / Cédula:</span>
            {{ $certificate->provider->tax_id ?? 'N/A' }}
        </div>
        <div class="info-row">
            <span class="label">Dirección:</span>
            {{ $certificate->provider->address ?? 'N/A' }}
        </div>
    </div>

    <h3>DETALLE DE RETENCIONES PRACTICADAS</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Concepto</th>
                <th>Cuenta</th>
                <th class="text-right">Base</th>
                <th class="text-center">%</th>
                <th class="text-right">Retención</th>
            </tr>
        </thead>
        <tbody>
            @foreach($certificate->details as $detail)
            <tr>
                <td>{{ $detail->expense->expense_date->format('d/m/Y') }}</td>
                <td>{{ $detail->retention_concept }}</td>
                <td>{{ $detail->retention_account_code }}</td>
                <td class="text-right">${{ number_format($detail->base_amount, 2) }}</td>
                <td class="text-center">{{ number_format($detail->retention_percentage, 2) }}%</td>
                <td class="text-right">${{ number_format($detail->retained_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <th>Total Base Sujeta a Retención:</th>
                <td class="text-right">${{ number_format($certificate->total_base, 2) }}</td>
            </tr>
            <tr>
                <th>Total Retenido:</th>
                <td class="text-right">${{ number_format($certificate->total_retained, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="signature-section">
        <p>
            Certificamos que las anteriores son las retenciones en la fuente practicadas
            al proveedor durante el año gravable {{ $certificate->year }}.
        </p>

        <div class="signature-line">
            <strong>{{ $certificate->issuedBy->name }}</strong><br>
            Administrador(a)<br>
            Fecha: {{ $certificate->issued_at->format('d/m/Y') }}
        </div>
    </div>

    <div class="footer">
        <p>
            Este certificado fue generado electrónicamente por el sistema Tavira.<br>
            {{ $certificate->conjuntoConfig->name }} - NIT: {{ $certificate->conjuntoConfig->nit ?? 'N/A' }}
        </p>
    </div>
</body>
</html>
