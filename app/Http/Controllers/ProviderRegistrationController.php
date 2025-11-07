<?php

namespace App\Http\Controllers;

use App\Mail\NewProviderRegistration;
use App\Mail\ProviderAccountCreated;
use App\Models\Central\Provider;
use App\Models\Central\ProviderCategory;
use App\Models\ProviderRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProviderRegistrationController extends Controller
{
    /**
     * Display a listing of provider registrations (Admin only).
     */
    public function index(Request $request)
    {
        $query = ProviderRegistration::query()->with(['reviewedBy', 'categories']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%");
            });
        }

        $registrations = $query->latest()->paginate(15)->withQueryString();

        // Get counts by status
        $pendingCount = ProviderRegistration::pending()->count();
        $approvedCount = ProviderRegistration::approved()->count();
        $rejectedCount = ProviderRegistration::rejected()->count();

        return Inertia::render('admin/ProviderRegistrations/Index', [
            'registrations' => $registrations,
            'filters' => $request->only(['status', 'search']),
            'stats' => [
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'total' => $pendingCount + $approvedCount + $rejectedCount,
            ],
        ]);
    }

    /**
     * Display the specified provider registration.
     */
    public function show(ProviderRegistration $registration)
    {
        $registration->load(['reviewedBy', 'categories']);

        return Inertia::render('admin/ProviderRegistrations/Show', [
            'registration' => $registration,
        ]);
    }

    /**
     * Show the form for editing the specified provider registration.
     */
    public function edit(ProviderRegistration $registration)
    {
        // Only allow editing pending registrations
        if ($registration->status !== 'pending') {
            return redirect()->route('admin.provider-registrations.show', $registration)
                ->with('error', 'Solo se pueden editar solicitudes pendientes.');
        }

        $registration->load('categories');
        $categories = ProviderCategory::active()->ordered()->get();

        return Inertia::render('admin/ProviderRegistrations/Edit', [
            'registration' => $registration,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified provider registration.
     */
    public function update(Request $request, ProviderRegistration $registration)
    {
        // Only allow editing pending registrations
        if ($registration->status !== 'pending') {
            return redirect()->route('admin.provider-registrations.show', $registration)
                ->with('error', 'Solo se pueden editar solicitudes pendientes.');
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'service_type' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:provider_categories,id',
        ]);

        try {
            $registration->update([
                'company_name' => $validated['company_name'],
                'contact_name' => $validated['contact_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'service_type' => $validated['service_type'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            // Sync categories
            $registration->categories()->sync($validated['category_ids']);

            Log::info('Provider registration updated', [
                'registration_id' => $registration->id,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('admin.provider-registrations.show', $registration)
                ->with('success', 'Solicitud actualizada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Provider registration update failed', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al actualizar la solicitud.');
        }
    }

    /**
     * Approve a provider registration and create a provider.
     */
    public function approve(Request $request, ProviderRegistration $registration)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Check if registration is already approved
            if ($registration->status === 'approved') {
                return redirect()->back()->with('error', 'Este proveedor ya fue aprobado.');
            }

            // Check if user with this email already exists
            $existingUser = User::where('email', $registration->email)->first();
            if ($existingUser) {
                return redirect()->back()->with('error', 'Ya existe un usuario con este correo electrónico.');
            }

            // Use database transaction to ensure all operations succeed or fail together
            DB::transaction(function () use ($registration, $validated) {
                // Create provider from registration in central database
                $provider = Provider::create([
                    'name' => $registration->company_name,
                    'category' => $registration->service_type,
                    'phone' => $registration->phone,
                    'email' => $registration->email,
                    'contact_name' => $registration->contact_name,
                    'contact_phone' => $registration->phone,
                    'contact_email' => $registration->email,
                    'notes' => $registration->description,
                    'is_active' => true,
                ]);

                // Sync categories from registration to provider
                $registration->load('categories');
                if ($registration->categories->isNotEmpty()) {
                    $provider->categories()->sync($registration->categories->pluck('id'));
                }

                // Create user account for provider
                $user = User::create([
                    'name' => $registration->contact_name,
                    'email' => $registration->email,
                    'password' => bcrypt(Str::random(32)), // Temporary random password
                    'email_verified_at' => now(), // Auto-verify email since admin approved
                ]);

                // Assign provider role
                $user->assignRole('provider');

                // Store provider_id in user's data (for easy access)
                $user->update([
                    'provider_id' => $provider->id,
                ]);

                // Generate password reset token
                $token = app('auth.password.broker')->createToken($user);

                // Update registration status
                $registration->update([
                    'status' => 'approved',
                    'admin_notes' => $validated['admin_notes'] ?? null,
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                ]);

                Log::info('Provider registration approved', [
                    'registration_id' => $registration->id,
                    'provider_id' => $provider->id,
                    'user_id' => $user->id,
                    'approved_by' => auth()->id(),
                ]);

                // Send account creation email with password setup link
                Mail::to($registration->email)->queue(new ProviderAccountCreated($user, $token, $provider));
            });

            return redirect()->back()->with('success', 'Proveedor aprobado exitosamente. Se ha enviado un correo para configurar su cuenta.');
        } catch (\Exception $e) {
            Log::error('Provider approval failed', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al aprobar el proveedor: '.$e->getMessage());
        }
    }

    /**
     * Reject a provider registration.
     */
    public function reject(Request $request, ProviderRegistration $registration)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        try {
            // Check if registration is already rejected
            if ($registration->status === 'rejected') {
                return redirect()->back()->with('error', 'Este proveedor ya fue rechazado.');
            }

            $registration->update([
                'status' => 'rejected',
                'admin_notes' => $validated['admin_notes'],
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            Log::info('Provider registration rejected', [
                'registration_id' => $registration->id,
                'rejected_by' => auth()->id(),
            ]);

            // TODO: Send rejection email to provider

            return redirect()->back()->with('success', 'Solicitud rechazada.');
        } catch (\Exception $e) {
            Log::error('Provider rejection failed', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al rechazar la solicitud.');
        }
    }

    /**
     * Show the provider registration form (public route).
     */
    public function create()
    {
        $categories = ProviderCategory::active()->ordered()->get();

        return Inertia::render('ProviderRegister', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new provider registration (public route).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'service_type' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:provider_categories,id',
        ]);

        try {
            // Create the provider registration
            $registration = ProviderRegistration::create([
                'company_name' => $validated['company_name'],
                'contact_name' => $validated['contact_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'service_type' => $validated['service_type'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => 'pending',
            ]);

            // Attach categories
            $registration->categories()->attach($validated['category_ids']);

            // Log the registration for admin notification
            Log::info('New provider registration', [
                'id' => $registration->id,
                'company' => $registration->company_name,
                'email' => $registration->email,
            ]);

            // Send email notification to all superadmins
            $superadmins = User::role('superadmin')->get();
            foreach ($superadmins as $admin) {
                Mail::to($admin->email)->queue(new NewProviderRegistration($registration));
            }

            Log::info('Admin notifications queued', [
                'registration_id' => $registration->id,
                'admins_notified' => $superadmins->count(),
            ]);

            // TODO: Send confirmation email to provider

            return redirect()->back()->with('success', '¡Gracias por tu interés! Hemos recibido tu solicitud y nos contactaremos contigo pronto.');
        } catch (\Exception $e) {
            Log::error('Provider registration failed', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al procesar tu solicitud. Por favor, intenta nuevamente.');
        }
    }
}
