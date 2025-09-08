<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

        // Check for existing active mass invitation
        $activeMassInvitation = Invitation::where('is_mass_invitation', true)
            ->where('expires_at', '>', now())
            ->first();

        return Inertia::render('admin/Invitations/Create', [
            'apartments' => $apartments,
            'roles' => [
                'admin_conjunto' => 'Administrador del Conjunto',
                'consejo' => 'Miembro del Consejo',
                'propietario' => 'Propietario',
                'residente' => 'Residente',
                'porteria' => 'Portería',
            ],
            'activeMassInvitation' => $activeMassInvitation,
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

    public function storeMass(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mass_invitation_title' => ['required', 'string', 'max:100'],
            'mass_invitation_description' => ['nullable', 'string', 'max:1000'],
            'role' => ['required', 'in:admin_conjunto,consejo,propietario,residente,porteria'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        // Check if there's already an active mass invitation
        $existingMassInvitation = Invitation::where('is_mass_invitation', true)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingMassInvitation) {
            return back()->with('error', 'Ya existe una invitación masiva activa. Debe expirar o eliminarse antes de crear una nueva.');
        }

        $invitation = Invitation::create([
            'email' => 'mass-invitation@tavira.com.co', // Placeholder email
            'role' => $validated['role'], // Use the selected role
            'apartment_id' => null,
            'invited_by' => auth()->id(),
            'message' => null,
            'is_mass_invitation' => true,
            'mass_invitation_title' => $validated['mass_invitation_title'],
            'mass_invitation_description' => $validated['mass_invitation_description'],
            'expires_at' => $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : now()->addDays(30),
        ]);

        return redirect()->route('invitations.index')
            ->with('success', 'Invitación masiva creada correctamente.');
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

    public function getRegistrationUrl(Invitation $invitation)
    {
        \Log::info('getRegistrationUrl called', [
            'invitation_id' => $invitation->id,
            'is_accepted' => $invitation->isAccepted(),
            'is_expired' => $invitation->isExpired(),
            'is_mass_invitation' => $invitation->is_mass_invitation,
        ]);

        // For mass invitations, don't check if accepted since multiple people can use it
        if (! $invitation->is_mass_invitation && $invitation->isAccepted()) {
            \Log::info('Individual invitation is accepted, redirecting');

            return redirect()->route('invitations.show', $invitation)->with('error', 'Esta invitación ya fue aceptada.');
        }

        if ($invitation->isExpired()) {
            \Log::info('Invitation is expired, redirecting');

            return redirect()->route('invitations.show', $invitation)->with('error', 'Esta invitación ha expirado.');
        }

        $registrationUrl = route('register', ['token' => $invitation->token]);
        \Log::info('Generated registration URL', ['url' => $registrationUrl]);

        // Generate QR code as SVG string
        $qrCode = (string) QrCode::format('svg')
            ->size(200)
            ->margin(2)
            ->generate($registrationUrl);

        \Log::info('Generated QR code', [
            'qr_length' => strlen($qrCode),
            'qr_type' => gettype($qrCode),
            'qr_first_100_chars' => substr($qrCode, 0, 100),
        ]);

        return Inertia::render('admin/Invitations/RegistrationUrl', [
            'invitation' => $invitation->load(['apartment.apartmentType']),
            'registrationUrl' => $registrationUrl,
            'qrCode' => $qrCode,
        ]);
    }
}
