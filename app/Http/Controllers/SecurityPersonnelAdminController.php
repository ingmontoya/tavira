<?php

namespace App\Http\Controllers;

use App\Models\SecurityPersonnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class SecurityPersonnelAdminController extends Controller
{
    /**
     * Display a listing of security personnel registrations.
     */
    public function index(Request $request)
    {
        $query = SecurityPersonnel::query();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by organization type
        if ($request->has('organization_type') && $request->organization_type !== 'all') {
            $query->where('organization_type', $request->organization_type);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('organization_name', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        $personnel = $query->latest()->paginate(15)->withQueryString();

        // Get counts by status
        $pendingEmailCount = SecurityPersonnel::where('status', 'pending_email_verification')->count();
        $pendingApprovalCount = SecurityPersonnel::where('status', 'pending_admin_approval')->count();
        $activeCount = SecurityPersonnel::where('status', 'active')->count();
        $rejectedCount = SecurityPersonnel::where('status', 'rejected')->count();
        $suspendedCount = SecurityPersonnel::where('status', 'suspended')->count();

        return Inertia::render('admin/SecurityPersonnel/Index', [
            'personnel' => $personnel,
            'filters' => $request->only(['status', 'search', 'organization_type']),
            'stats' => [
                'pending_email' => $pendingEmailCount,
                'pending_approval' => $pendingApprovalCount,
                'active' => $activeCount,
                'rejected' => $rejectedCount,
                'suspended' => $suspendedCount,
                'total' => $pendingEmailCount + $pendingApprovalCount + $activeCount + $rejectedCount + $suspendedCount,
            ],
            'organizationTypes' => [
                'policia' => 'Policia',
                'empresa_seguridad' => 'Empresa de Seguridad',
                'bomberos' => 'Bomberos',
                'ambulancia' => 'Ambulancia',
            ],
        ]);
    }

    /**
     * Display the specified security personnel.
     */
    public function show(SecurityPersonnel $personnel)
    {
        return Inertia::render('admin/SecurityPersonnel/Show', [
            'personnel' => $personnel,
            'organizationTypes' => [
                'policia' => 'Policia',
                'empresa_seguridad' => 'Empresa de Seguridad',
                'bomberos' => 'Bomberos',
                'ambulancia' => 'Ambulancia',
            ],
        ]);
    }

    /**
     * Approve a security personnel registration.
     */
    public function approve(Request $request, SecurityPersonnel $personnel)
    {
        // Only approve if pending admin approval
        if ($personnel->status !== 'pending_admin_approval') {
            return redirect()->back()->with('error', 'Solo se pueden aprobar solicitudes pendientes de aprobacion.');
        }

        try {
            $personnel->approve(auth()->id());

            Log::info('Security personnel approved', [
                'personnel_id' => $personnel->id,
                'approved_by' => auth()->id(),
            ]);

            // Send approval notification email
            $this->sendApprovalEmail($personnel);

            return redirect()->back()->with('success', 'Personal de seguridad aprobado exitosamente. Se ha enviado un correo de notificacion.');
        } catch (\Exception $e) {
            Log::error('Security personnel approval failed', [
                'personnel_id' => $personnel->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrio un error al aprobar la solicitud.');
        }
    }

    /**
     * Reject a security personnel registration.
     */
    public function reject(Request $request, SecurityPersonnel $personnel)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Debes proporcionar una razon para el rechazo.',
        ]);

        // Only reject if pending admin approval
        if ($personnel->status !== 'pending_admin_approval') {
            return redirect()->back()->with('error', 'Solo se pueden rechazar solicitudes pendientes de aprobacion.');
        }

        try {
            $personnel->reject(auth()->id(), $validated['reason']);

            Log::info('Security personnel rejected', [
                'personnel_id' => $personnel->id,
                'rejected_by' => auth()->id(),
                'reason' => $validated['reason'],
            ]);

            // Send rejection notification email
            $this->sendRejectionEmail($personnel, $validated['reason']);

            return redirect()->back()->with('success', 'Solicitud rechazada. Se ha enviado un correo de notificacion.');
        } catch (\Exception $e) {
            Log::error('Security personnel rejection failed', [
                'personnel_id' => $personnel->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrio un error al rechazar la solicitud.');
        }
    }

    /**
     * Suspend an active security personnel.
     */
    public function suspend(Request $request, SecurityPersonnel $personnel)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Debes proporcionar una razon para la suspension.',
        ]);

        // Only suspend if active
        if ($personnel->status !== 'active') {
            return redirect()->back()->with('error', 'Solo se pueden suspender cuentas activas.');
        }

        try {
            $personnel->suspend(auth()->id(), $validated['reason']);

            Log::info('Security personnel suspended', [
                'personnel_id' => $personnel->id,
                'suspended_by' => auth()->id(),
                'reason' => $validated['reason'],
            ]);

            // Send suspension notification email
            $this->sendSuspensionEmail($personnel, $validated['reason']);

            return redirect()->back()->with('success', 'Cuenta suspendida. Se ha enviado un correo de notificacion.');
        } catch (\Exception $e) {
            Log::error('Security personnel suspension failed', [
                'personnel_id' => $personnel->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrio un error al suspender la cuenta.');
        }
    }

    /**
     * Reactivate a suspended or rejected security personnel.
     */
    public function reactivate(Request $request, SecurityPersonnel $personnel)
    {
        // Only reactivate if suspended or rejected
        if (!in_array($personnel->status, ['suspended', 'rejected'])) {
            return redirect()->back()->with('error', 'Solo se pueden reactivar cuentas suspendidas o rechazadas.');
        }

        try {
            $personnel->approve(auth()->id());

            Log::info('Security personnel reactivated', [
                'personnel_id' => $personnel->id,
                'reactivated_by' => auth()->id(),
            ]);

            // Send reactivation notification email
            $this->sendReactivationEmail($personnel);

            return redirect()->back()->with('success', 'Cuenta reactivada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Security personnel reactivation failed', [
                'personnel_id' => $personnel->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrio un error al reactivar la cuenta.');
        }
    }

    /**
     * Send approval notification email.
     */
    private function sendApprovalEmail(SecurityPersonnel $personnel): void
    {
        Mail::send('emails.security-approved', [
            'name' => $personnel->name,
        ], function ($message) use ($personnel) {
            $message->to($personnel->email, $personnel->name)
                ->subject('Tu cuenta ha sido aprobada - Tavira Seguridad');
        });
    }

    /**
     * Send rejection notification email.
     */
    private function sendRejectionEmail(SecurityPersonnel $personnel, string $reason): void
    {
        Mail::send('emails.security-rejected', [
            'name' => $personnel->name,
            'reason' => $reason,
        ], function ($message) use ($personnel) {
            $message->to($personnel->email, $personnel->name)
                ->subject('Actualizacion sobre tu solicitud - Tavira Seguridad');
        });
    }

    /**
     * Send suspension notification email.
     */
    private function sendSuspensionEmail(SecurityPersonnel $personnel, string $reason): void
    {
        Mail::send('emails.security-suspended', [
            'name' => $personnel->name,
            'reason' => $reason,
        ], function ($message) use ($personnel) {
            $message->to($personnel->email, $personnel->name)
                ->subject('Tu cuenta ha sido suspendida - Tavira Seguridad');
        });
    }

    /**
     * Send reactivation notification email.
     */
    private function sendReactivationEmail(SecurityPersonnel $personnel): void
    {
        Mail::send('emails.security-reactivated', [
            'name' => $personnel->name,
        ], function ($message) use ($personnel) {
            $message->to($personnel->email, $personnel->name)
                ->subject('Tu cuenta ha sido reactivada - Tavira Seguridad');
        });
    }
}
