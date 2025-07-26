<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payment.early_discount_enabled', true);
        $this->migrator->add('payment.early_discount_days', 10);
        $this->migrator->add('payment.early_discount_percentage', 10.0);
        $this->migrator->add('payment.late_fees_enabled', true);
        $this->migrator->add('payment.late_fee_percentage', 2.0);
        $this->migrator->add('payment.late_fees_compound_monthly', true);
        $this->migrator->add('payment.grace_period_days', 0);
    }
};
