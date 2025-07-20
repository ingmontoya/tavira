<?php

namespace Database\Seeders;

use App\Models\Apartment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apartments = Apartment::all();
        
        foreach ($apartments as $apartment) {
            $rand = rand(1, 100);
            
            if ($rand <= 70) {
                // 70% current payments
                $apartment->update([
                    'payment_status' => 'current',
                    'last_payment_date' => now()->subDays(rand(1, 25)),
                    'outstanding_balance' => 0,
                ]);
            } elseif ($rand <= 85) {
                // 15% overdue 30 days
                $apartment->update([
                    'payment_status' => 'overdue_30',
                    'last_payment_date' => now()->subDays(rand(30, 45)),
                    'outstanding_balance' => $apartment->monthly_fee,
                ]);
            } elseif ($rand <= 92) {
                // 7% overdue 60 days
                $apartment->update([
                    'payment_status' => 'overdue_60',
                    'last_payment_date' => now()->subDays(rand(60, 75)),
                    'outstanding_balance' => $apartment->monthly_fee * 2,
                ]);
            } elseif ($rand <= 97) {
                // 5% overdue 90 days
                $apartment->update([
                    'payment_status' => 'overdue_90',
                    'last_payment_date' => now()->subDays(rand(90, 105)),
                    'outstanding_balance' => $apartment->monthly_fee * 3,
                ]);
            } else {
                // 3% overdue 90+ days
                $apartment->update([
                    'payment_status' => 'overdue_90_plus',
                    'last_payment_date' => now()->subDays(rand(120, 200)),
                    'outstanding_balance' => $apartment->monthly_fee * rand(4, 8),
                ]);
            }
        }
    }
}
