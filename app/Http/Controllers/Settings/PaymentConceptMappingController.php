<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccounts;
use App\Models\PaymentConcept;
use App\Models\PaymentConceptAccountMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentConceptMappingController extends Controller
{
    public function index()
    {
        $mappings = PaymentConceptAccountMapping::with([
            'paymentConcept',
            'incomeAccount',
            'receivableAccount'
        ])
        ->orderBy('payment_concept_id')
        ->get()
        ->map(function ($mapping) {
            return [
                'id' => $mapping->id,
                'payment_concept' => [
                    'id' => $mapping->paymentConcept->id,
                    'name' => $mapping->paymentConcept->name,
                    'type' => $mapping->paymentConcept->type,
                    'type_label' => $mapping->paymentConcept->type_label,
                ],
                'income_account' => $mapping->incomeAccount ? [
                    'id' => $mapping->incomeAccount->id,
                    'code' => $mapping->incomeAccount->code,
                    'name' => $mapping->incomeAccount->name,
                    'full_name' => $mapping->incomeAccount->code . ' - ' . $mapping->incomeAccount->name,
                ] : null,
                'receivable_account' => $mapping->receivableAccount ? [
                    'id' => $mapping->receivableAccount->id,
                    'code' => $mapping->receivableAccount->code,
                    'name' => $mapping->receivableAccount->name,
                    'full_name' => $mapping->receivableAccount->code . ' - ' . $mapping->receivableAccount->name,
                ] : null,
                'is_active' => $mapping->is_active,
                'notes' => $mapping->notes,
            ];
        });

        // Obtener conceptos sin mapeo
        $conceptsWithoutMapping = PaymentConcept::whereNotIn('id', 
            PaymentConceptAccountMapping::pluck('payment_concept_id')
        )->get()->map(function ($concept) {
            return [
                'id' => $concept->id,
                'name' => $concept->name,
                'type' => $concept->type,
                'type_label' => $concept->type_label,
            ];
        });

        // Obtener todas las cuentas para los selectores
        $incomeAccounts = ChartOfAccounts::where('account_type', 'income')
            ->orderBy('code')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'full_name' => $account->code . ' - ' . $account->name,
            ]);

        $assetAccounts = ChartOfAccounts::where('account_type', 'asset')
            ->where('code', 'LIKE', '13%') // Cuentas por cobrar
            ->orderBy('code')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'full_name' => $account->code . ' - ' . $account->name,
            ]);

        return Inertia::render('settings/PaymentConceptMapping/Index', [
            'mappings' => $mappings,
            'concepts_without_mapping' => $conceptsWithoutMapping,
            'income_accounts' => $incomeAccounts,
            'asset_accounts' => $assetAccounts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_concept_id' => 'required|exists:payment_concepts,id',
            'income_account_id' => 'required|exists:chart_of_accounts,id',
            'receivable_account_id' => 'required|exists:chart_of_accounts,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Verificar que no exista ya un mapeo para este concepto
        $existingMapping = PaymentConceptAccountMapping::where('payment_concept_id', $validated['payment_concept_id'])->first();
        
        if ($existingMapping) {
            return back()->withErrors([
                'payment_concept_id' => 'Ya existe un mapeo para este concepto de pago.'
            ]);
        }

        PaymentConceptAccountMapping::create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('success', 'Mapeo de cuentas creado exitosamente.');
    }

    public function update(Request $request, PaymentConceptAccountMapping $mapping)
    {
        $validated = $request->validate([
            'income_account_id' => 'required|exists:chart_of_accounts,id',
            'receivable_account_id' => 'required|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $mapping->update($validated);

        return back()->with('success', 'Mapeo de cuentas actualizado exitosamente.');
    }

    public function destroy(PaymentConceptAccountMapping $mapping)
    {
        // Verificar que no haya facturas que dependan de este mapeo
        $hasInvoices = DB::table('invoice_items')
            ->where('payment_concept_id', $mapping->payment_concept_id)
            ->exists();

        if ($hasInvoices) {
            return back()->withErrors([
                'delete' => 'No se puede eliminar este mapeo porque existen facturas que lo utilizan.'
            ]);
        }

        $mapping->delete();

        return back()->with('success', 'Mapeo de cuentas eliminado exitosamente.');
    }

    public function createDefaultMappings()
    {
        try {
            PaymentConceptAccountMapping::createDefaultMappings();
            
            return back()->with('success', 'Mapeos por defecto creados exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'default_mappings' => 'Error al crear mapeos por defecto: ' . $e->getMessage()
            ]);
        }
    }

    public function toggleActive(PaymentConceptAccountMapping $mapping)
    {
        $mapping->update(['is_active' => !$mapping->is_active]);

        $status = $mapping->is_active ? 'activado' : 'desactivado';
        
        return back()->with('success', "Mapeo {$status} exitosamente.");
    }
}