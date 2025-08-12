<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Administración - {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'DejaVu Sans', Arial, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            padding: 8px;
            color: #333;
            font-size: 9px;
            line-height: 1.2;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            margin-bottom: 8px;
        }

        .company-column {
            width: 50%;
            padding: 12px;
            border-right: 1px solid #333;
            vertical-align: top;
        }

        .client-invoice-column {
            width: 50%;
            padding: 12px;
            vertical-align: top;
        }

        .company-info h1 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            line-height: 1.1;
            color: #000;
            font-weight: bold;
        }

        .company-details {
            font-size: 8px;
            color: #000;
            line-height: 1.3;
        }

        .company-details div {
            margin-bottom: 3px;
        }

        .client-info {
            text-align: left;
            margin-bottom: 12px;
        }

        .client-title {
            font-size: 10px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            margin-bottom: 6px;
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        .client-details {
            font-size: 8px;
        }

        .invoice-info {
            text-align: left;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }

        .invoice-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 6px;
            text-transform: uppercase;
            text-align: center;
            color: #000;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        .invoice-details {
            font-size: 8px;
        }

        .detail-row {
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
        }

        .detail-row .label {
            font-weight: bold;
            color: #000;
            margin-right: 8px;
            width: 40%;
        }

        .concept-info {
            padding: 8px 12px;
            border: 1px solid #333;
            text-align: center;
            margin-bottom: 8px;
        }

        .concept-description {
            font-size: 9px;
            font-weight: bold;
            color: #000;
        }

        .payment-info {
            background: #f8f9fa;
            padding: 8px 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .payment-info .row {
            display: flex;
            margin-bottom: 4px;
        }

        .payment-info .label {
            width: 100px;
            font-weight: bold;
            color: #2c3e50;
            font-size: 8px;
        }

        .invoice-title {
            text-align: center;
            padding: 8px 0;
            background: #3498db;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .address-info {
            padding: 8px 12px;
            background: #ecf0f1;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #ddd;
            font-size: 8px;
        }

        .dates {
            padding: 8px 12px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #3498db;
            font-size: 9px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        .items-table th {
            background: #2c3e50;
            color: white;
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #1a2530;
            font-size: 8px;
        }

        .items-table td {
            padding: 6px 4px;
            border: 1px solid #ddd;
            font-size: 8px;
        }

        .items-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .amount-in-words {
            padding: 8px 12px;
            background: #e8f4fc;
            border-top: 1px dashed #3498db;
            border-bottom: 1px dashed #3498db;
            margin: 8px 0;
            font-style: italic;
            font-size: 8px;
        }

        .instructions {
            padding: 8px 12px;
            background: #f8f9fa;
            border-left: 2px solid #3498db;
            margin: 8px 0;
            line-height: 1.3;
            font-size: 8px;
        }

        .breakdown {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        .breakdown td {
            padding: 4px 8px;
            border: 1px solid #ddd;
            font-size: 8px;
        }

        .breakdown .section-title {
            background: #2c3e50;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }

        .breakdown .label {
            font-weight: bold;
            background: #f1f1f1;
            width: 70%;
        }

        .signature-area {
            padding: 8px;
            margin-top: 8px;
            display: flex;
            justify-content: space-around;
            border-top: 1px solid #3498db;
        }

        .signature-box {
            text-align: center;
            width: 45%;
            font-size: 7px;
        }

        .signature-line {
            height: 1px;
            background: #333;
            margin: 20px 0 5px;
        }

        .footer {
            text-align: center;
            padding: 6px;
            background: #2c3e50;
            color: white;
            font-size: 7px;
            line-height: 1.2;
        }

        .highlight {
            color: #e74c3c;
            font-weight: bold;
        }

        .total-amount {
            font-size: 10px;
            font-weight: bold;
            color: #c0392b;
        }

        .text-right {
            text-align: right;
        }

        .overdue-alert {
            background-color: #fee2e2;
            border: 1px solid #dc2626;
            color: #991b1b;
            padding: 6px;
            margin: 6px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 8px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .container {
                box-shadow: none;
                border-radius: 0;
                max-width: none;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if($invoice->status === 'vencido' || ($invoice->status === 'pendiente' && \Carbon\Carbon::parse($invoice->due_date)->isPast()))
            <div class="overdue-alert">
                ⚠️ RECIBO VENCIDO - Vencimiento: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                @if($invoice->days_overdue)
                    - {{ $invoice->days_overdue }} día{{ $invoice->days_overdue === 1 ? '' : 's' }} de retraso
                @endif
            </div>
        @endif

        <table class="header-table">
            <tr>
                <td class="company-column">
                    <div class="company-info">
                        <h1>{{ Str::upper($invoice->apartment->conjuntoConfig->name ?? 'TORRES DE VILLA CAMPESTRE') }}</h1>
                        <div class="company-details">
                            <div><strong>NIT:</strong> {{ $invoice->apartment->conjuntoConfig->nit ?? '900.123.456-7' }}</div>
                            <div><strong>Dirección:</strong> {{ $invoice->apartment->conjuntoConfig->address ?? 'CRA 26 No 3A - 272' }}</div>
                            <div><strong>Teléfono:</strong> {{ $invoice->apartment->conjuntoConfig->phone ?? '3045153' }}</div>
                            <div><strong>Email:</strong> {{ $invoice->apartment->conjuntoConfig->email ?? 'admin@conjunto.com' }}</div>
                        </div>
                    </div>
                </td>
                <td class="client-invoice-column">
                    <div class="client-info">
                        <div class="client-title">INFORMACIÓN DEL CLIENTE</div>
                        <div class="client-details">
                            <div class="detail-row">
                                <span class="label">Apartamento:</span>
                                <span>Torre {{ $invoice->apartment->tower }} - {{ $invoice->apartment->number }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Propietario:</span>
                                <span>{{ Str::upper(Str::limit($invoice->apartment->residents->where('resident_type', 'Owner')->first()->full_name ?? 'PROPIETARIO', 20)) }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Tipo:</span>
                                <span>{{ $invoice->apartment->apartmentType->name ?? 'N/A' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">ID Cliente:</span>
                                <span>{{ $invoice->apartment->number }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="invoice-info">
                        <div class="invoice-title">CUENTA DE COBRO No. {{ $invoice->invoice_number }}</div>
                        <div class="invoice-details">
                            <div class="detail-row">
                                <span class="label">Fecha Factura:</span>
                                <span>{{ \Carbon\Carbon::parse($invoice->billing_date)->format('d/m/Y') }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Fecha Vencimiento:</span>
                                <span>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Período:</span>
                                <span>{{ $invoice->billing_period_label }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Estado:</span>
                                <span>{{ $invoice->status_label }}</span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="concept-info">
            <div class="concept-description">
                POR CONCEPTO DE {{ Str::upper(Str::limit($invoice->items->first()->description ?? 'CUOTA DE ADMINISTRACIÓN', 35)) }} {{ Str::upper($invoice->billing_period_label) }}
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>U Medida</th>
                    <th>Valor Unitario</th>
                    <th>IVA</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                        <td>Und.</td>
                        <td>$ {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td>0%</td>
                        <td>$ {{ number_format($item->total_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="payment-info-table" style="width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 8px;">
            <tr>
                <td style="padding: 6px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold; width: 30%;">Valor en Letras:</td>
                <td style="padding: 6px; border: 1px solid #ddd;">
                    @php
                        // Función simple para convertir números a letras (versión básica)
                        $totalAmount = (int)$invoice->total_amount;
                        $amountInWords = number_format($totalAmount, 0, ',', '.') . ' PESOS COLOMBIANOS M/CTE';
                    @endphp
                    {{ Str::upper($amountInWords) }}
                </td>
            </tr>
            <tr>
                <td style="padding: 6px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold;">Consignar en:</td>
                <td style="padding: 6px; border: 1px solid #ddd;">CTA {{ $invoice->apartment->conjuntoConfig->bank_account ?? '0284-6999-3829' }} {{ $invoice->apartment->conjuntoConfig->bank_name ?? 'BANCO DAVIVIENDA' }}</td>
            </tr>
            <tr>
                <td style="padding: 6px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold;">Soporte:</td>
                <td style="padding: 6px; border: 1px solid #ddd;">{{ $invoice->apartment->conjuntoConfig->email ?? 'admin@conjunto.com' }}</td>
            </tr>
        </table>

        <table class="breakdown">
            <tr>
                <td colspan="2" class="section-title">CUENTA DE COBRO ACTUAL</td>
            </tr>
            <tr>
                <td class="label">CUOTA ADMINISTRACIÓN</td>
                <td>$ {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($invoice->early_discount > 0)
                <tr>
                    <td class="label">MENOS DESCUENTO</td>
                    <td>$ {{ number_format($invoice->early_discount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">NETO A PAGAR HASTA {{ \Carbon\Carbon::parse($invoice->billing_date)->addDays(10)->format('d') }}º DÍA</td>
                    <td>$ {{ number_format($invoice->subtotal - $invoice->early_discount, 0, ',', '.') }}</td>
                </tr>
            @endif

            <tr>
                <td colspan="2" class="section-title">DISCRIMINADO DE SALDOS</td>
            </tr>
            @if($invoice->late_fees > 0)
                <tr>
                    <td class="label">INTERESES DE MORA</td>
                    <td>$ {{ number_format($invoice->late_fees, 0, ',', '.') }}</td>
                </tr>
            @else
                <tr>
                    <td class="label">INTERESES DE MORA</td>
                    <td>$ 0,00</td>
                </tr>
            @endif
            <tr>
                <td class="label">CUOTAS EXTRAORDINARIAS</td>
                <td>$ 0,00</td>
            </tr>
            <tr>
                <td class="label">OTROS CONCEPTOS</td>
                <td>$ 0,00</td>
            </tr>
            @if($invoice->paid_amount > 0)
                <tr>
                    <td class="label">MENOS ANTICIPO</td>
                    <td>$ {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                </tr>
            @else
                <tr>
                    <td class="label">MENOS ANTICIPO</td>
                    <td>$ 0,00</td>
                </tr>
            @endif
            <tr>
                <td class="label">TOTAL FACTURA</td>
                <td class="total-amount">$ {{ number_format($invoice->balance_due, 2, ',', '.') }}</td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <tr>
                <td style="text-align: center; padding: 20px; width: 50%; border-top: 1px solid #3498db;">
                    <strong style="font-size: 8px;">Firma Administrador</strong><br>
                    <div style="border-bottom: 1px solid #333; margin: 20px 10px 5px; height: 1px;"></div>
                </td>
                <td style="text-align: center; padding: 20px; width: 50%; border-top: 1px solid #3498db;">
                    <strong style="font-size: 8px;">Firma Residente</strong><br>
                    <div style="border-bottom: 1px solid #333; margin: 20px 10px 5px; height: 1px;"></div>
                </td>
            </tr>
        </table>

        <div class="footer">
            SOPORTE AL CORREO: {{ $invoice->apartment->conjuntoConfig->email ?? 'admin@conjunto.com' }}
        </div>
    </div>
</body>
</html>