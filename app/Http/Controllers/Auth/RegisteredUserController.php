<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
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
    public function createAdmin(): Response|RedirectResponse
    {
        return Inertia::render('auth/RegisterAdmin');
    }

    /**
     * Show the registration page.
     */
    public function create(): Response|RedirectResponse
    {
        return Inertia::render('auth/RegisterAdmin');
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
