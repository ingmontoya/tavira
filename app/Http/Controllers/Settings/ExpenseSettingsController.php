<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Settings\ExpenseSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseSettingsController extends Controller
{
    public function index()
    {
        $settings = ExpenseSettings::make();

        // Get all expense categories for auto-approval configuration
        $expenseCategories = ExpenseCategory::with('conjuntoConfig')
            ->active()
            ->orderBy('name')
            ->get();

        return Inertia::render('Settings/ExpenseSettings', [
            'settings' => [
                'approval_required' => $settings->approval_required,
                'approval_threshold_amount' => $settings->approval_threshold_amount,
                'approval_threshold_currency' => $settings->approval_threshold_currency,
                'council_approval_required' => $settings->council_approval_required,
                'council_approval_notification_email' => $settings->council_approval_notification_email,
                'auto_approve_below_threshold' => $settings->auto_approve_below_threshold,
                'auto_approve_categories' => $settings->auto_approve_categories,
                'notify_on_pending_approval' => $settings->notify_on_pending_approval,
                'notify_on_approval_granted' => $settings->notify_on_approval_granted,
                'notify_on_approval_rejected' => $settings->notify_on_approval_rejected,
            ],
            'expenseCategories' => $expenseCategories,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'approval_required' => 'boolean',
            'approval_threshold_amount' => 'required|numeric|min:0',
            'approval_threshold_currency' => 'required|string|in:COP,USD',
            'council_approval_required' => 'boolean',
            'council_approval_notification_email' => 'nullable|email',
            'auto_approve_below_threshold' => 'boolean',
            'auto_approve_categories' => 'array',
            'auto_approve_categories.*' => 'exists:expense_categories,id',
            'notify_on_pending_approval' => 'boolean',
            'notify_on_approval_granted' => 'boolean',
            'notify_on_approval_rejected' => 'boolean',
        ]);

        $settings = ExpenseSettings::make();

        // Update all settings
        foreach ($validated as $key => $value) {
            $settings->$key = $value;
        }

        $settings->save();

        return redirect()->back()->with('success', 'Configuración de gastos actualizada exitosamente');
    }
}
