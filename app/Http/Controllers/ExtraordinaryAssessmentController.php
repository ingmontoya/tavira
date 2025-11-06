<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\ExtraordinaryAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExtraordinaryAssessmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->firstOrFail();

        $assessments = ExtraordinaryAssessment::where('conjunto_config_id', $conjunto->id)
            ->withCount('apartments')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    'name' => $assessment->name,
                    'description' => $assessment->description,
                    'total_amount' => $assessment->total_amount,
                    'total_collected' => $assessment->total_collected,
                    'total_pending' => $assessment->total_pending,
                    'number_of_installments' => $assessment->number_of_installments,
                    'installments_generated' => $assessment->installments_generated,
                    'start_date' => $assessment->start_date->format('Y-m-d'),
                    'end_date' => $assessment->end_date?->format('Y-m-d'),
                    'status' => $assessment->status,
                    'status_label' => $assessment->status_label,
                    'distribution_type' => $assessment->distribution_type,
                    'distribution_label' => $assessment->distribution_label,
                    'progress_percentage' => $assessment->progress_percentage,
                    'apartments_count' => $assessment->apartments_count,
                    'created_at' => $assessment->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Payments/ExtraordinaryAssessments/Index', [
            'assessments' => $assessments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->firstOrFail();

        $totalApartments = Apartment::where('conjunto_config_id', $conjunto->id)
            ->whereIn('status', ['Occupied', 'Available'])
            ->count();

        return Inertia::render('Payments/ExtraordinaryAssessments/Create', [
            'totalApartments' => $totalApartments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'number_of_installments' => 'required|integer|min:1|max:60',
            'start_date' => 'required|date',
            'distribution_type' => ['required', Rule::in(['equal', 'by_coefficient'])],
            'notes' => 'nullable|string',
        ]);

        $assessment = ExtraordinaryAssessment::create([
            ...$validated,
            'conjunto_config_id' => $conjunto->id,
            'status' => 'draft',
        ]);

        return redirect()->route('extraordinary-assessments.show', $assessment)
            ->with('success', 'Cuota extraordinaria creada exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(ExtraordinaryAssessment $extraordinaryAssessment)
    {
        $extraordinaryAssessment->load(['apartments.apartment']);

        $apartmentDetails = $extraordinaryAssessment->apartments->map(function ($assessmentApartment) {
            return [
                'id' => $assessmentApartment->id,
                'apartment_id' => $assessmentApartment->apartment_id,
                'apartment_number' => $assessmentApartment->apartment->number,
                'apartment_tower' => $assessmentApartment->apartment->tower,
                'total_amount' => $assessmentApartment->total_amount,
                'installment_amount' => $assessmentApartment->installment_amount,
                'installments_paid' => $assessmentApartment->installments_paid,
                'amount_paid' => $assessmentApartment->amount_paid,
                'amount_pending' => $assessmentApartment->amount_pending,
                'status' => $assessmentApartment->status,
                'status_label' => $assessmentApartment->status_label,
                'progress_percentage' => $assessmentApartment->progress_percentage,
                'first_payment_date' => $assessmentApartment->first_payment_date?->format('Y-m-d'),
                'last_payment_date' => $assessmentApartment->last_payment_date?->format('Y-m-d'),
            ];
        });

        return Inertia::render('Payments/ExtraordinaryAssessments/Show', [
            'assessment' => [
                'id' => $extraordinaryAssessment->id,
                'name' => $extraordinaryAssessment->name,
                'description' => $extraordinaryAssessment->description,
                'total_amount' => $extraordinaryAssessment->total_amount,
                'total_collected' => $extraordinaryAssessment->total_collected,
                'total_pending' => $extraordinaryAssessment->total_pending,
                'number_of_installments' => $extraordinaryAssessment->number_of_installments,
                'installments_generated' => $extraordinaryAssessment->installments_generated,
                'start_date' => $extraordinaryAssessment->start_date->format('Y-m-d'),
                'end_date' => $extraordinaryAssessment->end_date?->format('Y-m-d'),
                'status' => $extraordinaryAssessment->status,
                'status_label' => $extraordinaryAssessment->status_label,
                'distribution_type' => $extraordinaryAssessment->distribution_type,
                'distribution_label' => $extraordinaryAssessment->distribution_label,
                'progress_percentage' => $extraordinaryAssessment->progress_percentage,
                'notes' => $extraordinaryAssessment->notes,
                'created_at' => $extraordinaryAssessment->created_at->format('Y-m-d H:i'),
            ],
            'apartments' => $apartmentDetails,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExtraordinaryAssessment $extraordinaryAssessment)
    {
        // Solo se pueden editar las que están en draft
        if ($extraordinaryAssessment->status !== 'draft') {
            return redirect()->route('extraordinary-assessments.show', $extraordinaryAssessment)
                ->with('error', 'Solo se pueden editar cuotas extraordinarias en estado borrador');
        }

        return Inertia::render('Payments/ExtraordinaryAssessments/Edit', [
            'assessment' => [
                'id' => $extraordinaryAssessment->id,
                'name' => $extraordinaryAssessment->name,
                'description' => $extraordinaryAssessment->description,
                'total_amount' => $extraordinaryAssessment->total_amount,
                'number_of_installments' => $extraordinaryAssessment->number_of_installments,
                'start_date' => $extraordinaryAssessment->start_date->format('Y-m-d'),
                'distribution_type' => $extraordinaryAssessment->distribution_type,
                'notes' => $extraordinaryAssessment->notes,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExtraordinaryAssessment $extraordinaryAssessment)
    {
        // Solo se pueden editar las que están en draft
        if ($extraordinaryAssessment->status !== 'draft') {
            return redirect()->route('extraordinary-assessments.show', $extraordinaryAssessment)
                ->with('error', 'Solo se pueden editar cuotas extraordinarias en estado borrador');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'number_of_installments' => 'required|integer|min:1|max:60',
            'start_date' => 'required|date',
            'distribution_type' => ['required', Rule::in(['equal', 'by_coefficient'])],
            'notes' => 'nullable|string',
        ]);

        $extraordinaryAssessment->update($validated);

        return redirect()->route('extraordinary-assessments.show', $extraordinaryAssessment)
            ->with('success', 'Cuota extraordinaria actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExtraordinaryAssessment $extraordinaryAssessment)
    {
        // Solo se pueden eliminar las que están en draft
        if ($extraordinaryAssessment->status !== 'draft') {
            return redirect()->route('extraordinary-assessments.index')
                ->with('error', 'Solo se pueden eliminar cuotas extraordinarias en estado borrador');
        }

        $extraordinaryAssessment->delete();

        return redirect()->route('extraordinary-assessments.index')
            ->with('success', 'Cuota extraordinaria eliminada exitosamente');
    }

    /**
     * Activate an extraordinary assessment
     */
    public function activate(ExtraordinaryAssessment $extraordinaryAssessment)
    {
        try {
            DB::transaction(function () use ($extraordinaryAssessment) {
                $extraordinaryAssessment->activate();
            });

            return redirect()->route('extraordinary-assessments.show', $extraordinaryAssessment)
                ->with('success', 'Cuota extraordinaria activada exitosamente. Se ha asignado a todos los apartamentos.');
        } catch (\Exception $e) {
            return redirect()->route('extraordinary-assessments.show', $extraordinaryAssessment)
                ->with('error', 'Error al activar la cuota extraordinaria: '.$e->getMessage());
        }
    }

    /**
     * Cancel an extraordinary assessment
     */
    public function cancel(ExtraordinaryAssessment $extraordinaryAssessment)
    {
        if ($extraordinaryAssessment->status === 'completed') {
            return redirect()->route('extraordinary-assessments.show', $extraordinaryAssessment)
                ->with('error', 'No se puede cancelar una cuota extraordinaria completada');
        }

        $extraordinaryAssessment->status = 'cancelled';
        $extraordinaryAssessment->save();

        return redirect()->route('extraordinary-assessments.show', $extraordinaryAssessment)
            ->with('success', 'Cuota extraordinaria cancelada exitosamente');
    }

    /**
     * Get dashboard data
     */
    public function dashboard()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->firstOrFail();

        // Cuotas extraordinarias activas
        $activeAssessments = ExtraordinaryAssessment::where('conjunto_config_id', $conjunto->id)
            ->where('status', 'active')
            ->withCount('apartments')
            ->get()
            ->map(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    'name' => $assessment->name,
                    'total_amount' => $assessment->total_amount,
                    'total_collected' => $assessment->total_collected,
                    'total_pending' => $assessment->total_pending,
                    'progress_percentage' => $assessment->progress_percentage,
                    'installments_generated' => $assessment->installments_generated,
                    'number_of_installments' => $assessment->number_of_installments,
                ];
            });

        // Estadísticas generales
        $totalActive = ExtraordinaryAssessment::where('conjunto_config_id', $conjunto->id)
            ->where('status', 'active')
            ->count();

        $totalCollected = ExtraordinaryAssessment::where('conjunto_config_id', $conjunto->id)
            ->where('status', 'active')
            ->sum('total_collected');

        $totalPending = ExtraordinaryAssessment::where('conjunto_config_id', $conjunto->id)
            ->where('status', 'active')
            ->sum('total_pending');

        return Inertia::render('Payments/ExtraordinaryAssessments/Dashboard', [
            'activeAssessments' => $activeAssessments,
            'stats' => [
                'total_active' => $totalActive,
                'total_collected' => $totalCollected,
                'total_pending' => $totalPending,
            ],
        ]);
    }
}
