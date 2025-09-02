<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Tenant;
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
    public function createAdmin(): Response|RedirectResponse
    {
        return Inertia::render('auth/RegisterAdmin');
    }

    /**
     * Show the registration page.
     */
    public function create(): Response|RedirectResponse
    {
        $token = request('token');
        $hasAdmins = User::role(['superadmin', 'admin_conjunto', 'admin'])->exists();

        // If no token provided, show admin registration form
        if (! $token) {
            return Inertia::render('auth/RegisterAdmin');
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
                ->map(fn($apartment) => [
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
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('admin');

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', 'Cuenta de administrador creada exitosamente.');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('admin');

        event(new Registered($user));

        Auth::login($user);

        return to_route('verification.notice');
    }
}
