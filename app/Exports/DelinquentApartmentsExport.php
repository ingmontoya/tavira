<?php

namespace App\Exports;

use App\Models\Apartment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DelinquentApartmentsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function query()
    {
        return Apartment::with(['apartmentType', 'residents'])
            ->delinquent()
            ->orderBy('tower')
            ->orderBy('payment_status')
            ->orderBy('floor')
            ->orderBy('position_on_floor');
    }

    public function headings(): array
    {
        return [
            'Apartamento',
            'Torre',
            'Piso',
            'Tipo',
            'Estado de Pago',
            'Cuota Mensual',
            'Saldo Pendiente',
            'Último Pago',
            'Residentes',
            'Días de Mora',
        ];
    }

    public function map($apartment): array
    {
        $residentNames = $apartment->residents->map(function ($resident) {
            return $resident->first_name.' '.$resident->last_name;
        })->implode(', ');

        $daysOverdue = $this->calculateDaysOverdue($apartment->payment_status);

        return [
            $apartment->full_address,
            $apartment->tower,
            $apartment->floor,
            $apartment->apartmentType?->name ?? 'N/A',
            $apartment->paymentStatusBadge['text'],
            '$'.number_format($apartment->monthly_fee, 0, ',', '.'),
            '$'.number_format($apartment->outstanding_balance, 0, ',', '.'),
            $apartment->last_payment_date ?
                \Carbon\Carbon::parse($apartment->last_payment_date)->format('d/m/Y') : 'Nunca',
            $residentNames ?: 'Sin residentes',
            $daysOverdue,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ];
    }

    private function calculateDaysOverdue($paymentStatus): string
    {
        switch ($paymentStatus) {
            case 'overdue_30':
                return '30-59 días';
            case 'overdue_60':
                return '60-89 días';
            case 'overdue_90':
                return '90+ días';
            case 'overdue_90_plus':
                return '+90 días';
            default:
                return 'N/A';
        }
    }
}
