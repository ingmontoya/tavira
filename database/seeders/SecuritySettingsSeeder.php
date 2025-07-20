<?php

namespace Database\Seeders;

use App\Settings\SecuritySettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SecuritySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = new SecuritySettings;
        $group = $settings::group();
        $defaults = $settings->toArray();

        $table = config('settings.table_name', 'settings');

        foreach ($defaults as $name => $value) {
            DB::table($table)->updateOrInsert(
                [
                    'group' => $group,
                    'name' => $name,
                ],
                [
                    'payload' => json_encode($value),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
