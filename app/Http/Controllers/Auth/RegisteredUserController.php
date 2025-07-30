<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the admin registration page.
     */
    public function createAdmin(): Response
    {
        // Check if there are any admin users already registered
        $hasAdmins = User::role(['superadmin', 'admin_conjunto'])->exists();

        if ($hasAdmins) {
            return redirect()->route('login')
                ->with('error', 'Ya existe un administrador registrado. Use el sistema de invitaciones.');
        }

        return Inertia::render('auth/RegisterAdmin');
    }

    /**
     * Show the registration page.
     */
    public function create(): Response|RedirectResponse
    {
        $token = request('token');
        $hasAdmins = User::role(['superadmin', 'admin_conjunto'])->exists();

        // If no token provided
        if (! $token) {
            // If no admins exist, redirect to admin registration
            if (! $hasAdmins) {
                return redirect()->route('register.admin')
                    ->with('info', 'Registra la cuenta de administrador para comenzar.');
            }

            // If admins exist, allow free registration
            return Inertia::render('auth/Register', [
                'invitation' => null,
                'apartment' => null,
            ]);
        }

        // If token provided, validate invitation
        $invitation = Invitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        // For mass invitations, don't check if already accepted
        if (! $invitation || (! $invitation->is_mass_invitation && $invitation->accepted_at)) {
            return redirect()->route('access-request.create')
                ->with('error', 'La invitación es inválida o ha expirado.');
        }

        $apartments = null;
        if ($invitation->is_mass_invitation) {
            // Load all apartments for selection
            $apartments = \App\Models\Apartment::with('apartmentType')
                ->orderBy('tower')
                ->orderBy('floor')
                ->orderBy('number')
                ->get()
                ->map(fn ($apartment) => [
                    'id' => $apartment->id,
                    'number' => $apartment->number,
                    'tower' => $apartment->tower,
                    'floor' => $apartment->floor,
                    'apartment_type' => $apartment->apartmentType->name ?? null,
                ]);
        }

        return Inertia::render('auth/Register', [
            'invitation' => [
                'email' => $invitation->is_mass_invitation ? null : $invitation->email,
                'role' => $invitation->is_mass_invitation ? 'residente' : $invitation->role,
                'token' => $invitation->token,
                'is_mass_invitation' => $invitation->is_mass_invitation,
                'mass_invitation_title' => $invitation->mass_invitation_title,
                'mass_invitation_description' => $invitation->mass_invitation_description,
            ],
            'apartment' => $invitation->apartment?->only(['number', 'tower', 'floor']),
            'apartments' => $apartments,
        ]);
    }

    /**
     * Handle admin registration.
     */
    public function storeAdmin(RegisterRequest $request): RedirectResponse
    {
        // Check if there are any admin users already registered
        $hasAdmins = User::role(['superadmin', 'admin_conjunto'])->exists();

        if ($hasAdmins) {
            return redirect()->route('login')
                ->with('error', 'Ya existe un administrador registrado.');
        }

        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('admin_conjunto');

        event(new Registered($user));

        Auth::login($user);

        return to_route('verification.notice');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle invitation-based registration
        if (isset($validated['token'])) {
            $invitation = Invitation::where('token', $validated['token'])
                ->where('expires_at', '>', now())
                ->firstOrFail();

            // For mass invitations, don't check if already accepted
            if (! $invitation->is_mass_invitation && $invitation->accepted_at) {
                return redirect()->route('access-request.create')
                    ->with('error', 'Esta invitación ya ha sido utilizada.');
            }

            $email = $invitation->is_mass_invitation ? $validated['email'] : $invitation->email;
            $role = $invitation->is_mass_invitation ? 'residente' : $invitation->role;

            // Check if user with this email already exists
            if (User::where('email', $email)->exists()) {
                return back()->withErrors(['email' => 'Ya existe un usuario con este correo electrónico.']);
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole($role);

            // For mass invitations, store the apartment association for later resident profile completion
            // Note: Resident record creation is deferred until user completes their profile
            // with required fields like document_type, document_number, etc.
            if ($invitation->is_mass_invitation && isset($validated['apartment_id'])) {
                // TODO: Implement a mechanism to associate user with apartment
                // This could be done through a user profile completion flow
                // or by storing the apartment_id in user metadata
            }

            // For individual invitations, mark as accepted
            if (! $invitation->is_mass_invitation) {
                $invitation->update([
                    'accepted_at' => now(),
                    'accepted_by' => $user->id,
                ]);
            }
        } else {
            // Handle free registration (when admins exist)
            $hasAdmins = User::role(['superadmin', 'admin_conjunto'])->exists();

            if (! $hasAdmins) {
                return redirect()->route('register.admin')
                    ->with('error', 'Debe registrar un administrador primero.');
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Assign default role for free registration
            $user->assignRole('residente');
        }

        event(new Registered($user));

        Auth::login($user);

        return to_route('verification.notice');
    }
}
