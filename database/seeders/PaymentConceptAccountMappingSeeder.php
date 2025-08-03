<?php

namespace Database\Seeders;

use App\Models\PaymentConceptAccountMapping;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentConceptAccountMappingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create default mappings for all existing payment concepts
        PaymentConceptAccountMapping::createDefaultMappings();
        
        $this->command->info('✅ Mapeos por defecto de conceptos de pago creados exitosamente.');
    }
}