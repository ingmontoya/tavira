<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $query = EmailTemplate::query()
            ->with(['createdBy:id,name', 'updatedBy:id,name'])
            ->orderBy('type')
            ->orderBy('is_default', 'desc')
            ->orderBy('name');

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $templates = $query->paginate(15)->withQueryString();

        return Inertia::render('EmailTemplates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['type', 'status', 'search']),
            'types' => EmailTemplate::TYPES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('EmailTemplates/Create', [
            'types' => EmailTemplate::TYPES,
            'defaultVariables' => EmailTemplate::DEFAULT_VARIABLES,
            'defaultDesignConfig' => EmailTemplate::DEFAULT_DESIGN_CONFIG,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:'.implode(',', array_keys(EmailTemplate::TYPES)),
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'design_config' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $template = EmailTemplate::create($validated);

        // If this is set as default, make sure to unset other defaults of the same type
        if ($validated['is_default'] ?? false) {
            $template->setAsDefault();
        }

        return redirect()->route('email-templates.index')
            ->with('success', 'Plantilla de email creada exitosamente.');
    }

    public function show(EmailTemplate $emailTemplate): Response
    {
        $emailTemplate->load(['createdBy:id,name', 'updatedBy:id,name']);

        // Get a sample invoice for preview if it's an invoice template
        $sampleInvoice = null;
        if ($emailTemplate->type === 'invoice') {
            $sampleInvoice = Invoice::with(['apartment.conjuntoConfig'])
                ->whereNotNull('invoice_number')
                ->first();
        }

        return Inertia::render('EmailTemplates/Show', [
            'template' => $emailTemplate,
            'sampleInvoice' => $sampleInvoice,
            'types' => EmailTemplate::TYPES,
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): Response
    {
        return Inertia::render('EmailTemplates/Edit', [
            'template' => $emailTemplate,
            'types' => EmailTemplate::TYPES,
            'defaultVariables' => EmailTemplate::DEFAULT_VARIABLES,
            'defaultDesignConfig' => EmailTemplate::DEFAULT_DESIGN_CONFIG,
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:'.implode(',', array_keys(EmailTemplate::TYPES)),
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'design_config' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $emailTemplate->update($validated);

        // If this is set as default, make sure to unset other defaults of the same type
        if ($validated['is_default'] ?? false) {
            $emailTemplate->setAsDefault();
        }

        return redirect()->route('email-templates.index')
            ->with('success', 'Plantilla de email actualizada exitosamente.');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        // Prevent deletion of default templates
        if ($emailTemplate->is_default) {
            return back()->with('error', 'No se puede eliminar una plantilla marcada como predeterminada.');
        }

        $emailTemplate->delete();

        return redirect()->route('email-templates.index')
            ->with('success', 'Plantilla de email eliminada exitosamente.');
    }

    public function preview(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'data' => 'array',
        ]);

        $data = $validated['data'] ?? [];

        // If no data provided and it's an invoice template, use sample data
        if (empty($data) && $emailTemplate->type === 'invoice') {
            $sampleInvoice = Invoice::with(['apartment.conjuntoConfig'])
                ->whereNotNull('invoice_number')
                ->first();

            if ($sampleInvoice) {
                $data = [
                    'invoice_number' => $sampleInvoice->invoice_number,
                    'apartment_number' => $sampleInvoice->apartment->number,
                    'apartment_address' => $sampleInvoice->apartment->full_address,
                    'billing_period' => $sampleInvoice->billing_period_label,
                    'due_date' => $sampleInvoice->due_date->format('d/m/Y'),
                    'total_amount' => number_format($sampleInvoice->total_amount, 0, ',', '.'),
                    'balance_due' => number_format($sampleInvoice->balance_due, 0, ',', '.'),
                    'conjunto_name' => $sampleInvoice->apartment->conjuntoConfig->name ?? 'Conjunto Residencial',
                    'billing_date' => $sampleInvoice->billing_date->format('d/m/Y'),
                ];
            }
        }

        $processed = $emailTemplate->processTemplate($data);

        return response()->json([
            'subject' => $processed['subject'],
            'body' => $processed['body'],
            'design_config' => $processed['design_config'],
        ]);
    }

    public function setDefault(EmailTemplate $emailTemplate)
    {
        $emailTemplate->setAsDefault();

        return back()->with('success', 'Plantilla marcada como predeterminada exitosamente.');
    }

    public function toggleStatus(EmailTemplate $emailTemplate)
    {
        $emailTemplate->update([
            'is_active' => ! $emailTemplate->is_active,
        ]);

        $status = $emailTemplate->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Plantilla {$status} exitosamente.");
    }

    public function duplicate(EmailTemplate $emailTemplate)
    {
        $duplicated = $emailTemplate->replicate([
            'is_default', // Don't copy default status
        ]);

        $duplicated->name = $emailTemplate->name.' (Copia)';
        $duplicated->is_default = false;
        $duplicated->save();

        return redirect()->route('email-templates.edit', $duplicated)
            ->with('success', 'Plantilla duplicada exitosamente.');
    }
}
