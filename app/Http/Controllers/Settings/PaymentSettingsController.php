<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Settings\PaymentSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentSettingsController extends Controller
{
    public function index()
    {
        $settings = app(PaymentSettings::class);

        return Inertia::render('settings/PaymentSettings', [
            'settings' => [
                'early_discount_enabled' => $settings->early_discount_enabled,
                'early_discount_days' => $settings->early_discount_days,
                'early_discount_percentage' => $settings->early_discount_percentage,
                'late_fees_enabled' => $settings->late_fees_enabled,
                'late_fee_percentage' => $settings->late_fee_percentage,
                'late_fees_compound_monthly' => $settings->late_fees_compound_monthly,
                'grace_period_days' => $settings->grace_period_days,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'early_discount_enabled' => 'required|boolean',
            'early_discount_days' => 'required|integer|min:1|max:30',
            'early_discount_percentage' => 'required|numeric|min:0|max:100',
            'late_fees_enabled' => 'required|boolean',
            'late_fee_percentage' => 'required|numeric|min:0|max:100',
            'late_fees_compound_monthly' => 'required|boolean',
            'grace_period_days' => 'required|integer|min:0|max:30',
        ]);

        $settings = app(PaymentSettings::class);

        $settings->early_discount_enabled = $validated['early_discount_enabled'];
        $settings->early_discount_days = $validated['early_discount_days'];
        $settings->early_discount_percentage = $validated['early_discount_percentage'];
        $settings->late_fees_enabled = $validated['late_fees_enabled'];
        $settings->late_fee_percentage = $validated['late_fee_percentage'];
        $settings->late_fees_compound_monthly = $validated['late_fees_compound_monthly'];
        $settings->grace_period_days = $validated['grace_period_days'];

        $settings->save();

        return back();
    }
}
