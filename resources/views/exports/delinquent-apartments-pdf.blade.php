<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Listado de Morosos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .stat-item {
            text-align: center;
            flex: 1;
        }
        .stat-item .number {
            font-size: 18px;
            font-weight: bold;
            color: #dc2626;
        }
        .stat-item .label {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }
        .tower-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .tower-header {
            background-color: #e5e7eb;
            padding: 10px;
            font-weight: bold;
            border-left: 4px solid #6b7280;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        .status-overdue-30 {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-overdue-60 {
            background-color: #fed7aa;
            color: #ea580c;
        }
        .status-overdue-90 {
            background-color: #fecaca;
            color: #dc2626;
        }
        .status-overdue-90-plus {
            background-color: #fca5a5;
            color: #991b1b;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Listado de Morosos</h1>
        <p>Apartamentos con pagos pendientes</p>
        <p>Corte: {{ \Carbon\Carbon::parse($cutoffDate)->format('d/m/Y') }}</p>
    </div>

    <div class="stats">
        <div class="stat-item">
            <div class="number">{{ $stats['total_delinquent'] }}</div>
            <div class="label">Total Morosos</div>
        </div>
        <div class="stat-item">
            <div class="number">{{ $stats['overdue_30'] }}</div>
            <div class="label">30 días</div>
        </div>
        <div class="stat-item">
            <div class="number">{{ $stats['overdue_60'] }}</div>
            <div class="label">60 días</div>
        </div>
        <div class="stat-item">
            <div class="number">{{ $stats['overdue_90'] }}</div>
            <div class="label">90 días</div>
        </div>
        <div class="stat-item">
            <div class="number">{{ $stats['overdue_90_plus'] }}</div>
            <div class="label">+90 días</div>
        </div>
    </div>

    @if(count($delinquentApartments) > 0)
        @foreach($delinquentApartments as $tower => $apartments)
            <div class="tower-section">
                <div class="tower-header">
                    Torre {{ $tower }} - {{ count($apartments) }} morosos
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Apartamento</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Cuota Mensual</th>
                            <th>Saldo Pendiente</th>
                            <th>Último Pago</th>
                            <th>Residentes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($apartments as $apartment)
                            <tr>
                                <td>{{ $apartment->full_address }}</td>
                                <td>{{ $apartment->apartmentType?->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="status-badge status-{{ $apartment->payment_status }}">
                                        {{ $apartment->payment_status_badge['text'] }}
                                    </span>
                                </td>
                                <td>${{ number_format($apartment->monthly_fee, 0, ',', '.') }}</td>
                                <td>${{ number_format($apartment->outstanding_balance, 0, ',', '.') }}</td>
                                <td>
                                    {{ $apartment->last_payment_date ? 
                                        \Carbon\Carbon::parse($apartment->last_payment_date)->format('d/m/Y') : 'Nunca' }}
                                </td>
                                <td>
                                    @if($apartment->residents->count() > 0)
                                        {{ $apartment->residents->map(function($resident) {
                                            return $resident->first_name . ' ' . $resident->last_name;
                                        })->implode(', ') }}
                                    @else
                                        Sin residentes
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div class="empty-state">
            <h3>No hay apartamentos morosos</h3>
            <p>Todos los apartamentos están al día con sus pagos.</p>
        </div>
    @endif

    <div class="footer">
        Reporte generado el {{ $generatedAt }} | Habitta - Sistema de Gestión de Conjuntos Residenciales
    </div>
</body>
</html>