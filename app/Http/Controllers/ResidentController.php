<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ResidentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $query = Resident::with('apartment');

        if ($request->filled('search')) {
            dd('sup');
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('apartment', function ($apartmentQuery) use ($search) {
                      $apartmentQuery->where('number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('resident_type')) {
            $query->where('resident_type', $request->get('resident_type'));
        }

        if ($request->filled('tower')) {
            $query->byTower($request->get('tower'));
        }


        $residents = $query->orderBy('last_name')
                          ->paginate(15)
                          ->withQueryString();


        // Get towers for the single conjunto configuration
        $towers = Apartment::distinct()
                          ->pluck('tower')
                          ->filter()
                          ->sort()
                          ->values();

        return Inertia::render('residents/Index', [
            'residents' => $residents,
            'towers' => $towers,
            'filters' => $request->only(['search', 'status', 'resident_type', 'tower']),
        ]);
    }

    public function create(): Response
    {
        // Get available apartments for the single conjunto configuration
        $apartments = Apartment::with('apartmentType')
                              ->orderBy('tower')
                              ->orderBy('floor')
                              ->orderBy('number')
                              ->get();

        return Inertia::render('residents/Create', [
            'apartments' => $apartments
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:20'],
            'document_number' => ['required', 'string', 'max:50', 'unique:residents'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:residents'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:M,F,Other'],
            'emergency_contact' => ['nullable', 'string'],
            'apartment_id' => ['required', 'exists:apartments,id'],
            'resident_type' => ['required', 'in:Owner,Tenant,Family'],
            'status' => ['required', 'in:Active,Inactive'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'notes' => ['nullable', 'string'],
            'email_notifications' => ['boolean'],
            'whatsapp_notifications' => ['boolean'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
        ]);

        // Verify apartment exists (single conjunto application)
        $apartment = Apartment::find($validated['apartment_id']);
        if (!$apartment) {
            return back()->withErrors(['apartment_id' => 'Apartamento no válido.']);
        }

        Resident::create($validated);

        return redirect()->route('residents.index')->with('success', 'Residente creado exitosamente.');
    }

    public function show(Resident $resident): Response
    {
        return Inertia::render('residents/Show', [
            'resident' => $resident->load('apartment.apartmentType'),
        ]);
    }

    public function edit(Resident $resident): Response
    {
        // Get available apartments for the single conjunto configuration
        // Since this is a single conjunto application, we get all apartments
        $apartments = Apartment::with('apartmentType')
                              ->orderBy('tower')
                              ->orderBy('floor')
                              ->orderBy('number')
                              ->get();

        return Inertia::render('residents/Edit', [
            'resident' => $resident->load('apartment', 'apartment.apartmentType'),
            'apartments' => $apartments
        ]);
    }

    public function update(Request $request, Resident $resident): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:20'],
            'document_number' => ['required', 'string', 'max:50', Rule::unique('residents')->ignore($resident->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('residents')->ignore($resident->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:M,F,Other'],
            'emergency_contact' => ['nullable', 'string'],
            'apartment_id' => ['required', 'exists:apartments,id'],
            'resident_type' => ['required', 'in:Owner,Tenant,Family'],
            'status' => ['required', 'in:Active,Inactive'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'notes' => ['nullable', 'string'],
            'email_notifications' => ['boolean'],
            'whatsapp_notifications' => ['boolean'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
        ]);

        // Verify apartment exists (single conjunto application)
        $apartment = Apartment::find($validated['apartment_id']);
        if (!$apartment) {
            return back()->withErrors(['apartment_id' => 'Apartamento no válido.']);
        }

        $resident->update($validated);

        return redirect()->route('residents.index')->with('success', 'Residente actualizado exitosamente.');
    }

    public function destroy(Resident $resident): RedirectResponse
    {
        $resident->delete();

        return redirect()->route('residents.index')->with('success', 'Residente eliminado exitosamente.');
    }
}
