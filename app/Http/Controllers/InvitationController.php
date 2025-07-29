<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function index(): Response
    {
        $invitations = Invitation::with(['invitedBy', 'acceptedBy', 'apartment'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('admin/Invitations/Index', [
            'invitations' => $invitations,
        ]);
    }

    public function create(): Response
    {
        $apartments = Apartment::with('apartmentType')
            ->orderBy('tower')
            ->orderBy('floor')
            ->orderBy('number')
            ->get();

        return Inertia::render('admin/Invitations/Create', [
            'apartments' => $apartments,
            'roles' => [
                'admin_conjunto' => 'Administrador del Conjunto',
                'consejo' => 'Miembro del Consejo',
                'propietario' => 'Propietario',
                'residente' => 'Residente',
                'porteria' => 'Portería',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:admin_conjunto,consejo,propietario,residente,porteria'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'role' => $validated['role'],
            'apartment_id' => $validated['apartment_id'] ?? null,
            'invited_by' => auth()->id(),
            'message' => $validated['message'],
        ]);

        return redirect()->route('invitations.index')
            ->with('success', 'Invitación enviada correctamente.');
    }

    public function show(Invitation $invitation): Response
    {
        $invitation->load(['invitedBy', 'acceptedBy', 'apartment.apartmentType']);

        return Inertia::render('admin/Invitations/Show', [
            'invitation' => $invitation,
        ]);
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        $invitation->delete();

        return redirect()->route('invitations.index')
            ->with('success', 'Invitación eliminada correctamente.');
    }

    public function resend(Invitation $invitation): RedirectResponse
    {
        if ($invitation->isAccepted()) {
            return back()->with('error', 'Esta invitación ya fue aceptada.');
        }

        $invitation->update([
            'expires_at' => now()->addDays(7),
        ]);

        return back()->with('success', 'Invitación reenviada correctamente.');
    }
}
