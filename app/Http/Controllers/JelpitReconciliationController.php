<?php

namespace App\Http\Controllers;

use App\Imports\JelpitPaymentImport as JelpitImportClass;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\JelpitPaymentImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class JelpitReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $conjuntoId = $conjunto->id;

        $query = JelpitPaymentImport::forConjunto($conjuntoId)
            ->with(['apartment', 'payment', 'createdBy', 'processedBy'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by batch if provided
        if ($request->filled('batch')) {
            $query->byBatch($request->batch);
        }

        $imports = $query->paginate(20)->withQueryString();

        // Get summary statistics
        $statistics = [
            'total' => JelpitPaymentImport::forConjunto($conjuntoId)->count(),
            'pending' => JelpitPaymentImport::forConjunto($conjuntoId)->pending()->count(),
            'matched' => JelpitPaymentImport::forConjunto($conjuntoId)->matched()->count(),
            'manual_review' => JelpitPaymentImport::forConjunto($conjuntoId)->byStatus('manual_review')->count(),
            'total_amount' => JelpitPaymentImport::forConjunto($conjuntoId)->sum('transaction_amount'),
        ];

        // Get unique batches for filtering
        $batches = JelpitPaymentImport::forConjunto($conjuntoId)
            ->select('import_batch_id', DB::raw('MIN(created_at) as created_at'))
            ->groupBy('import_batch_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->import_batch_id,
                    'date' => $batch->created_at->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('Finance/JelpitReconciliation/Index', [
            'imports' => $imports,
            'statistics' => $statistics,
            'batches' => $batches,
            'filters' => $request->only(['status', 'batch']),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $conjuntoId = $conjunto->id;
        $batchId = Str::uuid()->toString();
        $userId = auth()->id();

        try {
            DB::beginTransaction();

            // Create the import instance
            $import = new JelpitImportClass($conjuntoId, $batchId, $userId);

            // Process the Excel file
            Excel::import($import, $request->file('file'));

            DB::commit();

            $processedCount = $import->getProcessedCount();
            $matchedCount = $import->getMatchedCount();

            return redirect()->route('finance.jelpit-reconciliation.index')
                ->with('success', "Archivo procesado exitosamente. {$processedCount} pagos importados, {$matchedCount} conciliados automáticamente.");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['file' => 'Error procesando el archivo: '.$e->getMessage()]);
        }
    }

    public function show(JelpitPaymentImport $import)
    {
        $this->authorizeImport('view', $import);

        $import->load(['apartment.apartmentType', 'payment', 'createdBy', 'processedBy']);

        // Get potential matches for manual reconciliation
        $potentialMatches = [];

        if ($import->reconciliation_status === 'manual_review') {
            // Try apartment number matches (partial)
            if ($import->reference_number) {
                $apartmentMatches = Apartment::forConjunto($import->conjunto_config_id)
                    ->where('number', 'LIKE', '%'.$import->reference_number.'%')
                    ->with('apartmentType')
                    ->get();

                foreach ($apartmentMatches as $apartment) {
                    $potentialMatches[] = [
                        'type' => 'apartment',
                        'apartment' => $apartment,
                        'confidence' => 'medium',
                        'reason' => 'Coincidencia parcial por número de apartamento',
                    ];
                }
            }

            // Try NIT matches
            if ($import->cleaned_nit) {
                $nitMatches = Apartment::forConjunto($import->conjunto_config_id)
                    ->whereHas('residents', function ($query) use ($import) {
                        $query->where('document_number', 'LIKE', '%'.$import->cleaned_nit.'%')
                            ->where('status', 'Active');
                    })
                    ->with(['apartmentType', 'residents' => function ($query) use ($import) {
                        $query->where('document_number', 'LIKE', '%'.$import->cleaned_nit.'%')
                            ->where('status', 'Active');
                    }])
                    ->get();

                foreach ($nitMatches as $apartment) {
                    foreach ($apartment->residents as $resident) {
                        $potentialMatches[] = [
                            'type' => 'nit',
                            'apartment' => $apartment,
                            'resident' => $resident,
                            'confidence' => 'high',
                            'reason' => "Coincidencia por documento: {$resident->full_name}",
                        ];
                    }
                }
            }
        }

        // Get all apartments for manual assignment dropdown
        $allApartments = Apartment::forConjunto($import->conjunto_config_id)
            ->with(['apartmentType', 'residents' => function ($query) {
                $query->where('status', 'Active');
            }])
            ->orderBy('number')
            ->get();

        return Inertia::render('Finance/JelpitReconciliation/Show', [
            'importItem' => $import,
            'potentialMatches' => $potentialMatches,
            'allApartments' => $allApartments,
        ]);
    }

    public function manualMatch(Request $request, JelpitPaymentImport $import)
    {
        $this->authorizeImport('update', $import);

        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $apartment = Apartment::findOrFail($request->apartment_id);

            // Verify apartment belongs to same conjunto
            if ($apartment->conjunto_config_id !== $import->conjunto_config_id) {
                throw new \Exception('El apartamento seleccionado no pertenece al mismo conjunto.');
            }

            $import->update([
                'apartment_id' => $apartment->id,
                'match_type' => 'manual',
                'reconciliation_status' => 'matched',
                'match_notes' => $request->notes ?? "Asignación manual a apartamento {$apartment->number}",
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Pago conciliado manualmente exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['apartment_id' => 'Error en la conciliación manual: '.$e->getMessage()]);
        }
    }

    public function createPayment(JelpitPaymentImport $import)
    {
        $this->authorizeImport('update', $import);

        if (! $import->can_create_payment) {
            return redirect()->back()
                ->withErrors(['payment' => 'No se puede crear el pago. Verificar que esté conciliado.']);
        }

        try {
            DB::beginTransaction();

            $payment = $import->createPayment();

            DB::commit();

            return redirect()->route('finance.payments.show', $payment)
                ->with('success', "Pago {$payment->payment_number} creado exitosamente desde Jelpit.");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['payment' => 'Error creando el pago: '.$e->getMessage()]);
        }
    }

    public function reject(Request $request, JelpitPaymentImport $import)
    {
        $this->authorizeImport('update', $import);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $import->update([
            'reconciliation_status' => 'rejected',
            'match_notes' => $request->reason,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Pago marcado como rechazado.');
    }

    public function batchProcess(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $conjuntoId = $conjunto->id;

        $request->validate([
            'action' => 'required|in:create_payments,reject_all',
            'import_ids' => 'required|array|min:1',
            'import_ids.*' => 'exists:jelpit_payment_imports,id',
            'reason' => 'required_if:action,reject_all|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $imports = JelpitPaymentImport::forConjunto($conjuntoId)
                ->whereIn('id', $request->import_ids)
                ->get();

            $processedCount = 0;

            foreach ($imports as $import) {
                if ($request->action === 'create_payments') {
                    if ($import->can_create_payment) {
                        $import->createPayment();
                        $processedCount++;
                    }
                } elseif ($request->action === 'reject_all') {
                    $import->update([
                        'reconciliation_status' => 'rejected',
                        'match_notes' => $request->reason,
                        'processed_by' => auth()->id(),
                        'processed_at' => now(),
                    ]);
                    $processedCount++;
                }
            }

            DB::commit();

            $actionText = $request->action === 'create_payments' ? 'pagos creados' : 'registros rechazados';

            return redirect()->back()
                ->with('success', "{$processedCount} {$actionText} exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['batch' => 'Error en el procesamiento por lotes: '.$e->getMessage()]);
        }
    }

    private function authorizeImport(string $action, ?JelpitPaymentImport $import = null)
    {
        // Basic authorization - ensure user belongs to same conjunto
        if ($import) {
            $conjunto = ConjuntoConfig::where('is_active', true)->first();
            if ($import->conjunto_config_id !== $conjunto->id) {
                abort(403, 'No autorizado para acceder a este recurso.');
            }
        }
    }
}
