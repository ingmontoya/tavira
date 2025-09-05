<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsTemplateExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                '202012345',              // student_code
                'Juan Carlos',            // first_name
                'Pérez González',         // last_name
                '1234567890',            // document_id
                '+ +44 7447 313219',      // phone
                'M',                     // gender (M/F/O)
                '1995-05-15',            // birth_date (YYYY-MM-DD)
                'juan.perez@gmail.com',  // personal_email
                'j.perez@universidad.edu', // institutional_email
                'A1',                    // group
                'Ingeniería de Sistemas', // program_name
                'Semestre 2024-1',       // semester_name
                60,                      // credits_completed
                120,                     // total_credits
                50.0,                    // progress_rate
                'Antioquia',             // dpto
                'Medellín',              // city
                'Calle 123 # 45-67',     // address
                'active',                // initial_status (active/inactive/graduated/suspended)
                'Colombia',               // country
            ],
            [
                '202012346',
                'María Elena',
                'González López',
                '9876543210',
                '+57 301 987 6543',
                'F',
                '1996-08-22',
                'maria.gonzalez@hotmail.com',
                'm.gonzalez@universidad.edu',
                'B2',
                'Administración de Empresas',
                'Semestre 2024-1',
                45,
                100,
                45.0,
                'Cundinamarca',
                'Bogotá',
                'Carrera 15 # 85-20',
                'active',
                'Colombia',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'student_code',         // Código de estudiante (obligatorio)
            'first_name',          // Nombres (obligatorio)
            'last_name',           // Apellidos (obligatorio)
            'document_id',         // Documento de identidad
            'phone',               // Teléfono
            'gender',              // Género (M/F/O)
            'birth_date',          // Fecha de nacimiento (YYYY-MM-DD)
            'personal_email',      // Email personal
            'institutional_email', // Email institucional
            'group',               // Grupo
            'program_name',        // Nombre del programa
            'semester_name',       // Nombre del semestre
            'credits_completed',   // Créditos completados
            'total_credits',       // Total de créditos
            'progress_rate',       // Tasa de progreso (%)
            'dpto',                // Departamento
            'city',                // Ciudad
            'address',             // Dirección
            'initial_status',      // Estado inicial (active/inactive/graduated/suspended)
            'country',              // País
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
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
            // Style the data rows
            '2:3' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // student_code
            'B' => 20, // first_name
            'C' => 20, // last_name
            'D' => 15, // document_id
            'E' => 18, // phone
            'F' => 8,  // gender
            'G' => 12, // birth_date
            'H' => 25, // personal_email
            'I' => 25, // institutional_email
            'J' => 8,  // group
            'K' => 30, // program_name
            'L' => 20, // semester_name
            'M' => 12, // credits_completed
            'N' => 12, // total_credits
            'O' => 12, // progress_rate
            'P' => 15, // dpto
            'Q' => 15, // city
            'R' => 25, // address
            'S' => 15, // initial_status
            'T' => 12, // country
        ];
    }
}
