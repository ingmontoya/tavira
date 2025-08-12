<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #2563eb;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .header p {
            margin: 2px 0;
            color: #666;
            font-size: 9px;
        }
        .invoice-details {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .invoice-left, .invoice-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .invoice-right {
            text-align: right;
        }
        .info-box {
            background-color: #f8fafc;
            padding: 8px;
            border-radius: 3px;
            margin-bottom: 10px;
        }
        .info-box h3 {
            margin: 0 0 5px 0;
            color: #1e40af;
            font-size: 11px;
        }
        .info-row {
            margin-bottom: 4px;
        }
        .label {
            font-weight: bold;
            color: #4b5563;
            display: inline-block;
            width: 100px;
            font-size: 9px;
        }
        .value {
            color: #1f2937;
            font-size: 9px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #2563eb;
            color: white;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        .items-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-section {
            width: 100%;
            margin-top: 10px;
        }
        .totals-table {
            width: 250px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        .totals-table .label {
            font-weight: bold;
            color: #4b5563;
            width: 150px;
        }
        .totals-table .value {
            text-align: right;
            color: #1f2937;
        }
        .total-row {
            background-color: #2563eb;
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        .balance-row {
            background-color: #dc2626;
            color: white;
            font-weight: bold;
        }
        .paid-row {
            background-color: #16a34a;
            color: white;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fbbf24;
            color: #92400e;
        }
        .status-paid {
            background-color: #10b981;
            color: white;
        }
        .status-overdue {
            background-color: #ef4444;
            color: white;
        }
        .status-partial {
            background-color: #f59e0b;
            color: white;
        }
        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 8px;
        }
        .notes {
            background-color: #fef3c7;
            border-left: 3px solid #f59e0b;
            padding: 8px;
            margin: 10px 0;
            font-size: 9px;
        }
        .overdue-alert {
            background-color: #fee2e2;
            border-left: 3px solid #ef4444;
            padding: 8px;
            margin: 10px 0;
            color: #991b1b;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $invoice->apartment->conjuntoConfig->name ?? 'Conjunto Residencial Vista Hermosa' }}</h1>
        <p>NIT: 900.123.456-7</p>
        <p>Administración de Propiedad Horizontal</p>
        <p>Bogotá D.C., Colombia</p>
    </div>

    <div class="invoice-details">
        <div class="invoice-left">
            <div class="info-box">
                <h3>Información del Apartamento</h3>
                <div class="info-row">
                    <span class="label">Apartamento:</span>
                    <span class="value">{{ $invoice->apartment->full_address }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Torre:</span>
                    <span class="value">{{ $invoice->apartment->tower }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Piso:</span>
                    <span class="value">{{ $invoice->apartment->floor }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Tipo:</span>
                    <span class="value">{{ $invoice->apartment->apartmentType->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        
        <div class="invoice-right">
            <div class="info-box">
                <h3>Información de Factura</h3>
                <div class="info-row">
                    <span class="label">Número:</span>
                    <span class="value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Fecha Emisión:</span>
                    <span class="value">{{ \Carbon\Carbon::parse($invoice->billing_date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Vencimiento:</span>
                    <span class="value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Período:</span>
                    <span class="value">{{ $invoice->billing_period_label }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Estado:</span>
                    <span class="value">
                        <span class="status-badge status-{{ $invoice->status }}">
                            {{ $invoice->status_badge['text'] }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if($invoice->status === 'overdue' || ($invoice->status === 'pending' && \Carbon\Carbon::parse($invoice->due_date)->isPast()))
        <div class="overdue-alert">
            <strong>⚠️ FACTURA VENCIDA</strong><br>
            Esta factura venció el {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}.
            @if($invoice->days_overdue)
                Lleva {{ $invoice->days_overdue }} día{{ $invoice->days_overdue === 1 ? '' : 's' }} de retraso.
            @endif
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Precio Unitario</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->description }}</strong>
                        @if($item->paymentConcept)
                            <br><small style="color: #6b7280;">{{ $item->paymentConcept->type_label }}</small>
                        @endif
                        @if($item->period_start && $item->period_end)
                            <br><small style="color: #6b7280;">
                                Período: {{ \Carbon\Carbon::parse($item->period_start)->format('d/m/Y') }} - 
                                {{ \Carbon\Carbon::parse($item->period_end)->format('d/m/Y') }}
                            </small>
                        @endif
                        @if($item->notes)
                            <br><small style="color: #6b7280;">{{ $item->notes }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="value">${{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($invoice->late_fees > 0)
                <tr>
                    <td class="label">Intereses de mora:</td>
                    <td class="value" style="color: #dc2626;">${{ number_format($invoice->late_fees, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td class="label">Total:</td>
                <td class="value">${{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
            </tr>
            @if($invoice->paid_amount > 0)
                <tr class="paid-row">
                    <td class="label">Pagado:</td>
                    <td class="value">-${{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($invoice->balance_due > 0)
                <tr class="balance-row">
                    <td class="label">Saldo Pendiente:</td>
                    <td class="value">${{ number_format($invoice->balance_due, 0, ',', '.') }}</td>
                </tr>
            @else
                <tr style="background-color: #16a34a; color: white;">
                    <td class="label">Estado:</td>
                    <td class="value">PAGADO</td>
                </tr>
            @endif
        </table>
    </div>

    @if($invoice->notes)
        <div class="notes">
            <strong>Notas:</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif

    <div class="footer">
        <p>Gracias por su pago oportuno. Para consultas, contáctenos al teléfono: (601) 123-4567</p>
        <p>Email: administracion@vistahermosa.com.co</p>
        <p>Este documento fue generado automáticamente el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>