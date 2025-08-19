<?php

namespace App\Services;

class DianDataService
{
    /**
     * Get default numbering ranges for DIAN electronic invoicing
     */
    public static function getDefaultNumberingRanges(): array
    {
        return [
            [
                'document_type' => 'FV - Factura de Venta',
                'from' => 1,
                'to' => 5000,
            ],
            [
                'document_type' => 'NC - Nota Crédito',
                'from' => 1,
                'to' => 1000,
            ],
            [
                'document_type' => 'ND - Nota Débito',
                'from' => 1,
                'to' => 1000,
            ],
        ];
    }

    /**
     * Get Colombian municipalities with DANE codes
     */
    public static function getDefaultMunicipalities(): array
    {
        return [
            // Principales ciudades de Colombia con códigos DANE
            ['code' => '11001', 'name' => 'Bogotá D.C.'],
            ['code' => '05001', 'name' => 'Medellín'],
            ['code' => '76001', 'name' => 'Cali'],
            ['code' => '08001', 'name' => 'Barranquilla'],
            ['code' => '13001', 'name' => 'Cartagena'],
            ['code' => '73001', 'name' => 'Ibagué'],
            ['code' => '17001', 'name' => 'Manizales'],
            ['code' => '66001', 'name' => 'Pereira'],
            ['code' => '23001', 'name' => 'Montería'],
            ['code' => '52001', 'name' => 'Pasto'],
            ['code' => '68001', 'name' => 'Bucaramanga'],
            ['code' => '70001', 'name' => 'Sincelejo'],
            ['code' => '15001', 'name' => 'Tunja'],
            ['code' => '19001', 'name' => 'Popayán'],
            ['code' => '85001', 'name' => 'Yopal'],
            ['code' => '50001', 'name' => 'Villavicencio'],
            ['code' => '54001', 'name' => 'Cúcuta'],
            ['code' => '86001', 'name' => 'Mocoa'],
            ['code' => '63001', 'name' => 'Armenia'],
            ['code' => '41001', 'name' => 'Neiva'],
            ['code' => '44001', 'name' => 'Riohacha'],
            ['code' => '47001', 'name' => 'Santa Marta'],
            ['code' => '20001', 'name' => 'Valledupar'],
            ['code' => '27001', 'name' => 'Quibdó'],
            ['code' => '18001', 'name' => 'Florencia'],
            ['code' => '81001', 'name' => 'Arauca'],
            ['code' => '88001', 'name' => 'San Andrés'],
            ['code' => '91001', 'name' => 'Leticia'],
            ['code' => '94001', 'name' => 'Inírida'],
            ['code' => '95001', 'name' => 'San José del Guaviare'],
            ['code' => '97001', 'name' => 'Mitú'],
            ['code' => '99001', 'name' => 'Puerto Carreño'],
        ];
    }

    /**
     * Get Colombian taxes applicable for electronic invoicing
     */
    public static function getDefaultTaxes(): array
    {
        return [
            [
                'code' => '01',
                'name' => 'IVA',
                'percentage' => 19.0,
            ],
            [
                'code' => '02',
                'name' => 'IC - Impuesto al Consumo',
                'percentage' => 8.0,
            ],
            [
                'code' => '03',
                'name' => 'ICA - Industria y Comercio',
                'percentage' => 1.0,
            ],
            [
                'code' => '04',
                'name' => 'IVA Exento',
                'percentage' => 0.0,
            ],
            [
                'code' => '05',
                'name' => 'IVA Excluido',
                'percentage' => 0.0,
            ],
            [
                'code' => '06',
                'name' => 'Retención en la Fuente',
                'percentage' => 2.5,
            ],
            [
                'code' => '07',
                'name' => 'Retención IVA',
                'percentage' => 15.0,
            ],
            [
                'code' => '08',
                'name' => 'Retención ICA',
                'percentage' => 1.0,
            ],
        ];
    }

    /**
     * Get measurement units for electronic invoicing
     */
    public static function getDefaultMeasurementUnits(): array
    {
        return [
            ['code' => 'NIU', 'description' => 'Número de items'],
            ['code' => 'KGM', 'description' => 'Kilogramo'],
            ['code' => 'MTR', 'description' => 'Metro'],
            ['code' => 'LTR', 'description' => 'Litro'],
            ['code' => 'MTK', 'description' => 'Metro cuadrado'],
            ['code' => 'MTQ', 'description' => 'Metro cúbico'],
            ['code' => 'HUR', 'description' => 'Hora'],
            ['code' => 'DAY', 'description' => 'Día'],
            ['code' => 'MON', 'description' => 'Mes'],
            ['code' => 'ANN', 'description' => 'Año'],
            ['code' => 'KWH', 'description' => 'Kilowatt hora'],
            ['code' => 'TNE', 'description' => 'Tonelada métrica'],
            ['code' => 'SET', 'description' => 'Conjunto'],
            ['code' => 'PR', 'description' => 'Par'],
            ['code' => 'DZN', 'description' => 'Docena'],
            ['code' => 'GRM', 'description' => 'Gramo'],
            ['code' => 'MLT', 'description' => 'Mililitro'],
            ['code' => 'CMT', 'description' => 'Centímetro'],
            ['code' => 'MMT', 'description' => 'Milímetro'],
            ['code' => 'INH', 'description' => 'Pulgada'],
            ['code' => 'FOT', 'description' => 'Pie'],
            ['code' => 'YRD', 'description' => 'Yarda'],
            ['code' => 'SMI', 'description' => 'Milla'],
            ['code' => 'GLI', 'description' => 'Galón imperial'],
            ['code' => 'GLL', 'description' => 'Galón US'],
            ['code' => 'ONZ', 'description' => 'Onza'],
            ['code' => 'LBR', 'description' => 'Libra'],
            ['code' => 'MIN', 'description' => 'Minuto'],
            ['code' => 'SEC', 'description' => 'Segundo'],
            ['code' => 'WEE', 'description' => 'Semana'],
        ];
    }
}
