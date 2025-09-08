<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the admin registration page.
     */
    public function createAdmin(): Response|RedirectResponse
    {
        return Inertia::render('auth/RegisterAdmin');
    }

    /**
     * Show the registration page.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        // Check if there's an invitation token
        $token = $request->get('token');
        $invitation = null;
        
        if ($token) {
            $invitation = Invitation::where('token', $token)
                ->where('expires_at', '>', now())
                ->first();
                
            if (!$invitation) {
                return redirect()->route('login')
                    ->with('error', 'El enlace de invitación es inválido o ha expirado.');
            }
            
            if (!$invitation->is_mass_invitation && $invitation->isAccepted()) {
                return redirect()->route('login')
                    ->with('error', 'Esta invitación ya fue utilizada.');
            }
        }
        
        $apartments = [];
        if ($invitation && $invitation->is_mass_invitation) {
            $apartments = \App\Models\Apartment::with('apartmentType')
                ->orderBy('tower')
                ->orderBy('floor')
                ->orderBy('number')
                ->get()
                ->map(function ($apartment) {
                    return [
                        'id' => $apartment->id,
                        'number' => $apartment->number,
                        'tower' => $apartment->tower,
                        'floor' => $apartment->floor,
                        'apartment_type' => $apartment->apartmentType?->name ?? 'Sin tipo'
                    ];
                });
        }

        return Inertia::render('auth/Register', [
            'invitation' => $invitation,
            'apartments' => $apartments,
        ]);
    }

    /**
     * Handle admin registration.
     */
    public function storeAdmin(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('admin_conjunto');

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', 'Cuenta de administrador central creada exitosamente. Después de verificar tu correo deberás seleccionar un plan para poder acceder a tu plataforma.');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Check for invitation token
        $token = $request->get('token');
        $invitation = null;
        $role = 'admin_conjunto'; // Default role
        
        if ($token) {
            $invitation = Invitation::where('token', $token)
                ->where('expires_at', '>', now())
                ->first();
                
            if (!$invitation) {
                return back()->with('error', 'El enlace de invitación es inválido o ha expirado.');
            }
            
            if (!$invitation->is_mass_invitation && $invitation->isAccepted()) {
                return back()->with('error', 'Esta invitación ya fue utilizada.');
            }
            
            // Check if email matches for individual invitations
            if (!$invitation->is_mass_invitation && $invitation->email !== $validated['email']) {
                return back()->with('error', 'El email no coincide con la invitación.');
            }
            
            $role = $invitation->role;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($role);
        
        // Handle apartment assignment for mass invitations
        if ($invitation && $invitation->is_mass_invitation && $request->has('apartment_id')) {
            $apartmentId = $request->get('apartment_id');
            if ($apartmentId) {
                // Split the full name into first and last names
                $nameParts = explode(' ', $validated['name'], 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';
                
                // Create a resident record with required fields
                \App\Models\Resident::create([
                    'apartment_id' => $apartmentId,
                    'document_type' => 'CC', // Default to Cédula de Ciudadanía
                    'document_number' => 'PENDIENTE_' . time(), // Temporary unique value
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $validated['email'],
                    'resident_type' => in_array($role, ['propietario']) ? 'Owner' : 'Tenant',
                    'start_date' => now()->toDateString(),
                ]);
            }
        }
        
        // Mark invitation as accepted if it exists
        if ($invitation && !$invitation->is_mass_invitation) {
            $invitation->update([
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return to_route('verification.notice');
    }
}
