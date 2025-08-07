<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ExpenseSettings extends Settings
{
    // Expense Approval Settings
    public bool $approval_required = true;

    public float $approval_threshold_amount = 4000000.0; // 4 salarios mínimos (aproximadamente $1,400,000 COP c/u)

    public string $approval_threshold_currency = 'COP';

    public bool $council_approval_required = true;

    public string $council_approval_notification_email = '';

    // Auto-Approval Settings
    public bool $auto_approve_below_threshold = false;

    public array $auto_approve_categories = [];

    // Notification Settings
    public bool $notify_on_pending_approval = true;

    public bool $notify_on_approval_granted = true;

    public bool $notify_on_approval_rejected = true;

    public static function group(): string
    {
        return 'expense';
    }
}
