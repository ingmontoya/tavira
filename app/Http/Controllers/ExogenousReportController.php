<?php

namespace App\Http\Controllers;

use App\Models\ExogenousReport;
use App\Models\ExogenousReportConfiguration;
use App\Services\ExogenousReporting\ExogenousReportExporter;
use App\Services\ExogenousReporting\ExogenousReportGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExogenousReportController extends Controller
{
    private ExogenousReportGenerator $generator;

    private ExogenousReportExporter $exporter;

    public function __construct(
        ExogenousReportGenerator $generator,
        ExogenousReportExporter $exporter
    ) {
        $this->generator = $generator;
        $this->exporter = $exporter;
    }

    /**
     * Display a listing of exogenous reports
     */
    public function index(Request $request)
    {
        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();

        $query = ExogenousReport::forConjunto($conjunto->id)
            ->with(['createdBy', 'validatedBy', 'exportedBy'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('fiscal_year')) {
            $query->byFiscalYear($request->fiscal_year);
        }

        if ($request->has('report_type')) {
            $query->byType($request->report_type);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        $reports = $query->paginate(15);

        return Inertia::render('Accounting/ExogenousReports/Index', [
            'reports' => $reports,
            'filters' => $request->only(['fiscal_year', 'report_type', 'status']),
            'availableYears' => $this->getAvailableYears($conjunto->id),
        ]);
    }

    /**
     * Show the form for creating a new report
     */
    public function create(Request $request)
    {
        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();
        $currentYear = now()->year;

        // Get configurations for the current year
        $configurations = ExogenousReportConfiguration::forConjunto($conjunto->id)
            ->byFiscalYear($currentYear)
            ->get()
            ->map(function ($config) {
                return [
                    'report_type' => $config->report_type,
                    'report_type_label' => $config->report_type_label,
                    'is_configured' => $config->is_configured,
                    'minimum_reporting_amount' => $config->minimum_reporting_amount,
                ];
            });

        return Inertia::render('Accounting/ExogenousReports/Create', [
            'availableYears' => range($currentYear - 5, $currentYear),
            'configurations' => $configurations,
            'reportTypes' => $this->getReportTypes(),
        ]);
    }

    /**
     * Preview report before generation
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|string|in:1001,1003,1005,1647',
            'fiscal_year' => 'required|integer|min:2020|max:2030',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();

        $periodStart = $validated['period_start'] ? Carbon::parse($validated['period_start']) : null;
        $periodEnd = $validated['period_end'] ? Carbon::parse($validated['period_end']) : null;

        try {
            $preview = $this->generator->previewReport(
                $conjunto->id,
                $validated['report_type'],
                $validated['fiscal_year'],
                $periodStart,
                $periodEnd
            );

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Store a newly created report
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|string|in:1001,1003,1005,1647',
            'fiscal_year' => 'required|integer|min:2020|max:2030',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'notes' => 'nullable|string|max:1000',
        ]);

        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();

        $periodStart = $validated['period_start'] ? Carbon::parse($validated['period_start']) : null;
        $periodEnd = $validated['period_end'] ? Carbon::parse($validated['period_end']) : null;

        try {
            $report = $this->generator->generateReport(
                $conjunto->id,
                $validated['report_type'],
                $validated['fiscal_year'],
                $periodStart,
                $periodEnd
            );

            if (isset($validated['notes'])) {
                $report->update(['notes' => $validated['notes']]);
            }

            return redirect()
                ->route('accounting.exogenous-reports.show', $report->id)
                ->with('success', 'Reporte de información exógena generado exitosamente');
        } catch (\Exception $e) {
            \Log::error('Error generating exogenous report', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified report
     */
    public function show(Request $request, ExogenousReport $exogenousReport)
    {
        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();

        // Authorization check
        if ($exogenousReport->conjunto_config_id !== $conjunto->id) {
            abort(403);
        }

        $exogenousReport->load([
            'items.provider',
            'createdBy',
            'validatedBy',
            'exportedBy',
            'conjuntoConfig',
        ]);

        // Get configuration
        $configuration = ExogenousReportConfiguration::forConjunto($exogenousReport->conjunto_config_id)
            ->byFiscalYear($exogenousReport->fiscal_year)
            ->byReportType($exogenousReport->report_type)
            ->first();

        return Inertia::render('Accounting/ExogenousReports/Show', [
            'report' => $exogenousReport,
            'configuration' => $configuration,
        ]);
    }

    /**
     * Validate a report
     */
    public function validateReport(Request $request, ExogenousReport $exogenousReport)
    {
        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();

        // Authorization check
        if ($exogenousReport->conjunto_config_id !== $conjunto->id) {
            abort(403);
        }

        try {
            $exogenousReport->validate($request->user()->id);

            return back()->with('success', 'Reporte validado exitosamente');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Export a report
     */
    public function export(Request $request, ExogenousReport $exogenousReport)
    {
        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();

        // Authorization check
        if ($exogenousReport->conjunto_config_id !== $conjunto->id) {
            abort(403);
        }

        $validated = $request->validate([
            'format' => 'required|string|in:xml,txt,excel',
        ]);

        try {
            $result = $this->exporter->export($exogenousReport, $validated['format']);

            return response()->json([
                'success' => true,
                'message' => 'Reporte exportado exitosamente',
                'file_path' => $result['file_path'],
                'file_name' => $result['file_name'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download exported report
     */
    public function download(Request $request, ExogenousReport $exogenousReport)
    {
        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();

        // Authorization check
        if ($exogenousReport->conjunto_config_id !== $conjunto->id) {
            abort(403);
        }

        try {
            return $this->exporter->download($exogenousReport);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Mark report as submitted to DIAN
     */
    public function markAsSubmitted(Request $request, ExogenousReport $exogenousReport)
    {
        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();

        // Authorization check
        if ($exogenousReport->conjunto_config_id !== $conjunto->id) {
            abort(403);
        }

        try {
            $exogenousReport->markAsSubmitted($request->user()->id);

            return back()->with('success', 'Reporte marcado como presentado a la DIAN');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified report
     */
    public function destroy(Request $request, ExogenousReport $exogenousReport)
    {
        $conjunto = \App\Models\ConjuntoConfig::where('is_active', true)->first();

        // Authorization check
        if ($exogenousReport->conjunto_config_id !== $conjunto->id) {
            abort(403);
        }

        if (! $exogenousReport->can_be_deleted) {
            return back()->withErrors(['error' => 'Este reporte no puede ser eliminado']);
        }

        $exogenousReport->delete();

        return redirect()
            ->route('accounting.exogenous-reports.index')
            ->with('success', 'Reporte eliminado exitosamente');
    }

    /**
     * Get available fiscal years with reports
     */
    private function getAvailableYears(int $conjuntoConfigId): array
    {
        $years = ExogenousReport::forConjunto($conjuntoConfigId)
            ->distinct()
            ->pluck('fiscal_year')
            ->toArray();

        // Add current year if not present
        $currentYear = now()->year;
        if (! in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }

        rsort($years);

        return $years;
    }

    /**
     * Get available report types
     */
    private function getReportTypes(): array
    {
        return [
            [
                'code' => '1001',
                'label' => 'Formato 1001 - Pagos y Retenciones',
                'description' => 'Pagos o abonos en cuenta y retenciones practicadas',
            ],
            [
                'code' => '1003',
                'label' => 'Formato 1003 - Retenciones en la Fuente',
                'description' => 'Retenciones en la fuente practicadas',
            ],
            [
                'code' => '1005',
                'label' => 'Formato 1005 - Ingresos Recibidos',
                'description' => 'Ingresos recibidos para terceros',
            ],
            [
                'code' => '1647',
                'label' => 'Formato 1647 - Retenciones 1.5%',
                'description' => 'Retenciones practicadas con tarifa del 1.5%',
            ],
        ];
    }
}
