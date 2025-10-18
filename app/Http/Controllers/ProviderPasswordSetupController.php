<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class ProviderPasswordSetupController extends Controller
{
    /**
     * Display the password setup form.
     */
    public function show(Request $request)
    {
        return Inertia::render('auth/ProviderPasswordSetup', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle the password setup request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Attempt to reset the user's password.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                Log::info('Provider password configured', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
        );

        // If the password was successfully reset, redirect to login.
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', '¡Contraseña configurada exitosamente! Ya puedes iniciar sesión.');
        }

        // Otherwise, return with an error.
        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
