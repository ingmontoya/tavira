<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanCreateMultipleConjuntos
{
    /**
     * Handle an incoming request.
     * 
     * This middleware restricts the creation of multiple conjuntos to company users only.
     * Individual users can create/manage one conjunto, but companies can create multiple.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Company users can always create multiple conjuntos
        if ($user->isCompany()) {
            return $next($request);
        }
        
        // For individual users, check if they're trying to create a second conjunto
        if ($user->isIndividual()) {
            // If user already has a conjunto and is trying to create another one
            if ($user->conjunto_config_id && $request->route()->getName() === 'conjunto-config.create') {
                abort(403, 'Los usuarios individuales solo pueden gestionar un conjunto. Actualiza tu cuenta a empresa para crear múltiples conjuntos.');
            }
            
            // If user is trying to store a new conjunto but already has one
            if ($user->conjunto_config_id && $request->route()->getName() === 'conjunto-config.store') {
                return back()->withErrors(['error' => 'Los usuarios individuales solo pueden gestionar un conjunto.']);
            }
        }

        return $next($request);
    }
}
