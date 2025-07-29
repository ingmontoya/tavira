<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('AccessRequest', [
            'conjunto' => [
                'name' => 'Conjunto Residencial Vista Hermosa',
                'description' => 'Plataforma de gestión integral para residentes y propietarios',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'apartment_number' => ['required', 'string', 'max:10'],
            'tower' => ['required', 'string', 'max:5'],
            'relationship' => ['required', 'in:propietario,residente'],
            'phone' => ['nullable', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        return back()->with('success',
            'Tu solicitud ha sido enviada correctamente. Un administrador la revisará y te contactará pronto.'
        );
    }
}
