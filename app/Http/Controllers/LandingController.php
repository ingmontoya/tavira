<?php

namespace App\Http\Controllers;

use App\Services\PerfexCrmService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LandingController extends Controller
{
    public function __construct(
        private PerfexCrmService $perfexCrmService
    ) {}

    /**
     * Show the landing page for contacto
     */
    public function contacto(): Response
    {
        return Inertia::render('Landing/Contacto');
    }

    /**
     * Process the contact form submission via API (JSON response)
     */
    public function submitContactoFormApi(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'conjunto_name' => 'required|string|max:255',
            'num_units' => 'required|integer|min:1|max:10000',
            'role' => 'required|string|max:255',
            'message' => 'nullable|string|max:2000',
            'lead_source' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor complete todos los campos requeridos.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        // Prepare lead data for Perfex CRM
        $leadData = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'conjunto_name' => $validatedData['conjunto_name'],
            'num_units' => $validatedData['num_units'],
            'role' => $validatedData['role'],
            'lead_source' => $validatedData['lead_source'] ?? 'Website - Landing Contacto',
            'tags' => 'Contacto,Landing Page,Prospecto',
        ];

        // Build description
        $description = "Solicitud de demostración desde Landing Page\n\n";
        $description .= "Rol: {$validatedData['role']}\n";
        $description .= "Conjunto: {$validatedData['conjunto_name']}\n";
        $description .= "Número de Unidades: {$validatedData['num_units']}\n";

        if (!empty($validatedData['message'])) {
            $description .= "\nMensaje adicional:\n{$validatedData['message']}";
        }

        $leadData['description'] = $description;
        $leadData['company'] = $validatedData['conjunto_name'];
        $leadData['title'] = $validatedData['role'];
        $leadData['country'] = 'Colombia';

        // Create lead in Perfex CRM
        $result = $this->perfexCrmService->createLead($leadData);

        if ($result['success']) {
            Log::info('Lead created successfully in Perfex CRM', [
                'email' => $validatedData['email'],
                'conjunto' => $validatedData['conjunto_name']
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Gracias por su interés! Un asesor se contactará pronto con usted.'
            ]);
        }

        // Log the error but still show success to the user
        Log::error('Failed to create lead in Perfex CRM', [
            'email' => $validatedData['email'],
            'error' => $result['error'] ?? 'Unknown error'
        ]);

        // Store in database as fallback
        $this->storeLeadLocally($validatedData);

        return response()->json([
            'success' => true,
            'message' => '¡Gracias por su interés! Un asesor se contactará pronto con usted.'
        ]);
    }

    /**
     * Process the contact form submission
     */
    public function submitContactoForm(Request $request): RedirectResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'conjunto_name' => 'required|string|max:255',
            'num_units' => 'required|integer|min:1|max:10000',
            'role' => 'required|string|max:255',
            'message' => 'nullable|string|max:2000',
            'lead_source' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();

        // Prepare lead data for Perfex CRM
        $leadData = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'conjunto_name' => $validatedData['conjunto_name'],
            'num_units' => $validatedData['num_units'],
            'role' => $validatedData['role'],
            'lead_source' => $validatedData['lead_source'] ?? 'Website - Landing Contacto',
            'tags' => 'Contacto,Landing Page,Prospecto',
        ];

        // Build description
        $description = "Solicitud de demostración desde Landing Page\n\n";
        $description .= "Rol: {$validatedData['role']}\n";
        $description .= "Conjunto: {$validatedData['conjunto_name']}\n";
        $description .= "Número de Unidades: {$validatedData['num_units']}\n";

        if (!empty($validatedData['message'])) {
            $description .= "\nMensaje adicional:\n{$validatedData['message']}";
        }

        $leadData['description'] = $description;

        // Company name is the conjunto name
        $leadData['company'] = $validatedData['conjunto_name'];

        // Title is their role in the council
        $leadData['title'] = $validatedData['role'];

        // Country is Colombia by default
        $leadData['country'] = 'Colombia';

        // Create lead in Perfex CRM
        $result = $this->perfexCrmService->createLead($leadData);

        if ($result['success']) {
            Log::info('Lead created successfully in Perfex CRM', [
                'email' => $validatedData['email'],
                'conjunto' => $validatedData['conjunto_name']
            ]);

            // Send notification email (optional - can be implemented later)
            // $this->sendNotificationEmail($validatedData);

            return redirect()->back()->with([
                'success' => true,
                'message' => '¡Gracias por su interés! Un asesor se contactará pronto con usted.'
            ]);
        }

        // Log the error but still show success to the user
        // We don't want to expose CRM errors to end users
        Log::error('Failed to create lead in Perfex CRM', [
            'email' => $validatedData['email'],
            'error' => $result['error'] ?? 'Unknown error'
        ]);

        // Store in database as fallback (optional)
        $this->storeLeadLocally($validatedData);

        return redirect()->back()->with([
            'success' => true,
            'message' => '¡Gracias por su interés! Un asesor se contactará pronto con usted.'
        ]);
    }

    /**
     * Store lead locally as fallback
     */
    private function storeLeadLocally(array $data): void
    {
        try {
            // You can create a Lead model and store it in your database
            // For now, just log it
            Log::info('Storing lead locally as fallback', [
                'lead_data' => $data
            ]);

            // Example:
            // Lead::create($data);
        } catch (\Exception $e) {
            Log::error('Failed to store lead locally', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification email to sales team
     */
    private function sendNotificationEmail(array $data): void
    {
        try {
            // Implement email notification if needed
            // Mail::to(config('app.sales_email'))->send(new NewLeadNotification($data));
        } catch (\Exception $e) {
            Log::error('Failed to send notification email', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
