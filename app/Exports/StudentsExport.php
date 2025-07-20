<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected $students;

    public function __construct(Collection $students)
    {
        $this->students = $students;
    }

    public function collection()
    {
        return $this->students;
    }

    public function map($student): array
    {
        return [
            $student->student_code,
            $student->first_name,
            $student->last_name,
            $student->document_id,
            $student->phone,
            $student->gender,
            $student->birth_date,
            $student->personal_email,
            $student->institutional_email,
            $student->group,
            $student->program ? $student->program->name : '',
            $student->currentSemester ? $student->currentSemester->name : '',
            $student->credits_completed,
            $student->total_credits,
            $student->progress_rate,
            $student->dpto,
            $student->city,
            $student->address,
            $student->initial_status,
            $student->country,
            $student->created_at ? $student->created_at->format('Y-m-d H:i:s') : '',
            $student->updated_at ? $student->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }

    public function headings(): array
    {
        return [
            'Código de Estudiante',
            'Nombres',
            'Apellidos',
            'Documento de Identidad',
            'Teléfono',
            'Género',
            'Fecha de Nacimiento',
            'Email Personal',
            'Email Institucional',
            'Grupo',
            'Programa',
            'Semestre Actual',
            'Créditos Completados',
            'Total Créditos',
            'Tasa de Progreso (%)',
            'Departamento',
            'Ciudad',
            'Dirección',
            'Estado Inicial',
            'País',
            'Fecha de Creación',
            'Última Actualización',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F2937'], // Dark gray
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, // student_code
            'B' => 20, // first_name
            'C' => 20, // last_name
            'D' => 18, // document_id
            'E' => 18, // phone
            'F' => 10, // gender
            'G' => 15, // birth_date
            'H' => 30, // personal_email
            'I' => 30, // institutional_email
            'J' => 10, // group
            'K' => 35, // program
            'L' => 20, // semester
            'M' => 15, // credits_completed
            'N' => 15, // total_credits
            'O' => 15, // progress_rate
            'P' => 20, // dpto
            'Q' => 20, // city
            'R' => 30, // address
            'S' => 15, // initial_status
            'T' => 15, // country
            'U' => 20, // created_at
            'V' => 20, // updated_at
        ];
    }
}
