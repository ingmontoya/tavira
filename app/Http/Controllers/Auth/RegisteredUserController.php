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
     * Show the registration page.
     */
    public function create(): Response|RedirectResponse
    {
        $token = request('token');

        if (! $token) {
            return redirect()->route('access-request.create')
                ->with('error', 'Se requiere una invitación válida para registrarse.');
        }

        $invitation = Invitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->first();

        if (! $invitation) {
            return redirect()->route('access-request.create')
                ->with('error', 'La invitación es inválida o ha expirado.');
        }

        return Inertia::render('auth/Register', [
            'invitation' => $invitation->only(['email', 'role', 'token']),
            'apartment' => $invitation->apartment?->only(['number', 'tower', 'floor']),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $invitation = Invitation::where('token', $validated['token'])
            ->where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($invitation->role);

        $invitation->update([
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('verification.notice');
    }
}
