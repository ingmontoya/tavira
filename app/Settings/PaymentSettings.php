<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    // Early Payment Discount Settings
    public bool $early_discount_enabled = true;

    public int $early_discount_days = 10;

    public float $early_discount_percentage = 10.0;

    // Late Fee Settings
    public bool $late_fees_enabled = true;

    public float $late_fee_percentage = 2.0;

    public bool $late_fees_compound_monthly = true;

    public int $grace_period_days = 0;

    public static function group(): string
    {
        return 'payment';
    }
}
