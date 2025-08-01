<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ResidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('ResidentSeeder skipped - no default conjuntos exist.');
        $this->command->info('Users must create conjunto configurations first, then add residents through the application.');
    }
}
