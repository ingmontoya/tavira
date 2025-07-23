<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #3b82f6;
            margin: 0;
            font-size: 28px;
        }
        .invoice-info {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .invoice-info h2 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #64748b;
        }
        .value {
            color: #1e293b;
        }
        .total {
            background-color: #3b82f6;
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
        }
        .attachment-note {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $invoice->apartment->conjuntoConfig->name ?? 'Conjunto Residencial' }}</h1>
            <p>Factura de Administración</p>
        </div>

        <div class="invoice-info">
            <h2>Información de la Factura</h2>
            <div class="info-row">
                <span class="label">Número de Factura:</span>
                <span class="value">{{ $invoice->invoice_number }}</span>
            </div>
            <div class="info-row">
                <span class="label">Apartamento:</span>
                <span class="value">{{ $invoice->apartment->full_address }}</span>
            </div>
            <div class="info-row">
                <span class="label">Período de Facturación:</span>
                <span class="value">{{ $invoice->billing_period_label }}</span>
            </div>
            <div class="info-row">
                <span class="label">Fecha de Emisión:</span>
                <span class="value">{{ \Carbon\Carbon::parse($invoice->billing_date)->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Fecha de Vencimiento:</span>
                <span class="value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span>
            </div>
        </div>

        <div class="total">
            Total a Pagar: ${{ number_format($invoice->balance_due, 0, ',', '.') }}
        </div>

        <div class="attachment-note">
            <strong>📎 Archivo Adjunto:</strong> Encontrará la factura detallada en formato PDF adjunta a este correo.
        </div>

        <p>Estimado/a propietario/a,</p>
        
        <p>Le enviamos la factura correspondiente al período de {{ $invoice->billing_period_label }} para su apartamento <strong>{{ $invoice->apartment->full_address }}</strong>.</p>
        
        <p>El monto total de la factura es de <strong>${{ number_format($invoice->total_amount, 0, ',', '.') }}</strong> con fecha límite de pago el <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</strong>.</p>

        @if($invoice->balance_due != $invoice->total_amount)
            <p>Su saldo pendiente actual es de <strong>${{ number_format($invoice->balance_due, 0, ',', '.') }}</strong> después de los pagos realizados.</p>
        @endif

        <p>Por favor, realice el pago antes de la fecha de vencimiento para evitar intereses de mora. Para cualquier consulta o aclaración, no dude en contactarnos.</p>

        <div class="footer">
            <p>Este es un correo automático, por favor no responda a esta dirección.</p>
            <p>{{ $invoice->apartment->conjuntoConfig->name ?? 'Conjunto Residencial' }}</p>
        </div>
    </div>
</body>
</html>