<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .payment-summary {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .payment-summary h3 {
            margin-top: 0;
            color: #495057;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
        }
        .detail-value {
            color: #495057;
            font-weight: 500;
        }
        .amount {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
        }
        .applications {
            margin-top: 30px;
        }
        .applications h3 {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
        .application-item {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .custom-message {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Comprobante de Pago</h1>
            <p>{{ $payment->payment_number }}</p>
        </div>
        
        <div class="content">
            @if($customMessage)
                <div class="custom-message">
                    <strong>Mensaje personalizado:</strong><br>
                    {{ $customMessage }}
                </div>
            @endif

            <div class="payment-summary">
                <h3>Resumen del Pago</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Número de Pago:</span>
                    <span class="detail-value">{{ $payment->payment_number }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Apartamento:</span>
                    <span class="detail-value">{{ $payment->apartment->number }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Fecha de Pago:</span>
                    <span class="detail-value">{{ $payment->payment_date->format('d/m/Y') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Método de Pago:</span>
                    <span class="detail-value">{{ $payment->payment_method_label }}</span>
                </div>
                
                @if($payment->reference_number)
                <div class="detail-row">
                    <span class="detail-label">Número de Referencia:</span>
                    <span class="detail-value">{{ $payment->reference_number }}</span>
                </div>
                @endif
                
                <div class="detail-row">
                    <span class="detail-label">Monto Total:</span>
                    <span class="detail-value amount">${{ number_format($payment->total_amount, 0, ',', '.') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value">{{ $payment->status_label }}</span>
                </div>
            </div>

            @if($includeApplications && $payment->applications->count() > 0)
                <div class="applications">
                    <h3>Aplicaciones del Pago</h3>
                    <p>Este pago fue aplicado a las siguientes facturas:</p>
                    
                    @foreach($payment->applications as $application)
                        @if($application->status === 'activo' || $application->status === 'active')
                            <div class="application-item">
                                <div class="detail-row">
                                    <span class="detail-label">Factura:</span>
                                    <span class="detail-value">{{ $application->invoice->invoice_number }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Monto Aplicado:</span>
                                    <span class="detail-value">${{ number_format($application->amount_applied, 0, ',', '.') }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Fecha de Aplicación:</span>
                                    <span class="detail-value">{{ $application->applied_date->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            @if($payment->notes)
                <div class="custom-message">
                    <strong>Observaciones:</strong><br>
                    {{ $payment->notes }}
                </div>
            @endif
        </div>
        
        <div class="footer">
            <p>Este es un comprobante automático generado por el sistema de gestión.</p>
            <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>