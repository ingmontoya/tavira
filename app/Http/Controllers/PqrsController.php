<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Pqrs;
use App\Models\PqrsAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PqrsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pqrs::with(['apartment', 'submittedBy', 'assignedTo', 'attachments']);

        $user = Auth::user();

        // Role-based filtering
        if ($user->hasRole('residente') || $user->hasRole('propietario')) {
            // Residents can only see their own PQRS
            $query->where('submitted_by', $user->id);
        }

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ticket_number', 'like', '%'.$request->search.'%')
                    ->orWhere('subject', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%')
                    ->orWhere('contact_name', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('apartment_id') && $user->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            $query->where('apartment_id', $request->apartment_id);
        }

        if ($request->filled('assigned_to') && $user->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $pqrs = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $apartments = [];
        $administrators = [];
        if ($user->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            $apartments = Apartment::orderBy('tower')->orderBy('floor')->orderBy('position_on_floor')->get();
            $administrators = User::role(['admin_conjunto', 'superadmin'])->get();
        }

        return Inertia::render('PQRS/Index', [
            'pqrs' => $pqrs,
            'apartments' => $apartments,
            'administrators' => $administrators,
            'filters' => $request->only(['search', 'type', 'status', 'priority', 'apartment_id', 'assigned_to']),
            'canCreate' => true, // All authenticated users can create PQRS
            'canManage' => $user->hasAnyRole(['admin_conjunto', 'superadmin']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        
        $apartments = [];
        if ($user->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            $apartments = Apartment::with(['apartmentType', 'residents'])
                ->orderBy('tower')
                ->orderBy('floor')
                ->orderBy('position_on_floor')
                ->get();
        }

        return Inertia::render('PQRS/Create', [
            'apartments' => $apartments,
            'userApartment' => $user->apartment,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'type' => ['required', Rule::in(['peticion', 'queja', 'reclamo', 'sugerencia'])],
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => ['required', Rule::in(['baja', 'media', 'alta', 'urgente'])],
            'apartment_id' => 'nullable|exists:apartments,id',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
        ]);

        $conjuntoConfig = ConjuntoConfig::first();

        $pqrs = Pqrs::create([
            'conjunto_config_id' => $conjuntoConfig->id,
            'type' => $request->type,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'apartment_id' => $request->apartment_id ?? $user->apartment?->id,
            'submitted_by' => $user->id,
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('pqrs/attachments', $filename, 'public');

                PqrsAttachment::create([
                    'pqrs_id' => $pqrs->id,
                    'filename' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'file_path' => $path,
                    'type' => $this->determineAttachmentType($file),
                    'uploaded_by' => $user->id,
                ]);
            }
        }

        return redirect()->route('pqrs.index')->with('success', 'PQRS creado exitosamente. Número de ticket: ' . $pqrs->ticket_number);
    }

    /**
     * Display the specified resource.
     */
    public function show(Pqrs $pqrs)
    {
        $user = Auth::user();

        // Check authorization
        if ($user->hasRole('residente') || $user->hasRole('propietario')) {
            if ($pqrs->submitted_by !== $user->id) {
                abort(403, 'No tienes autorización para ver este PQRS.');
            }
        }

        $pqrs->load([
            'apartment.apartmentType',
            'submittedBy',
            'assignedTo',
            'resolvedBy',
            'attachments.uploadedBy'
        ]);

        $administrators = [];
        if ($user->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            $administrators = User::role(['admin_conjunto', 'superadmin'])->get();
        }

        return Inertia::render('PQRS/Show', [
            'pqrs' => $pqrs,
            'administrators' => $administrators,
            'canEdit' => $pqrs->submitted_by === $user->id && $pqrs->isOpen(),
            'canManage' => $user->hasAnyRole(['admin_conjunto', 'superadmin']),
            'canRate' => $pqrs->submitted_by === $user->id && $pqrs->canBeRated(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pqrs $pqrs)
    {
        $user = Auth::user();

        // Only allow editing by the submitter and only if still open
        if ($pqrs->submitted_by !== $user->id || !$pqrs->isOpen()) {
            abort(403, 'No puedes editar este PQRS.');
        }

        $pqrs->load(['attachments']);

        $apartments = [];
        if ($user->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            $apartments = Apartment::with(['apartmentType', 'residents'])
                ->orderBy('tower')
                ->orderBy('floor')
                ->orderBy('position_on_floor')
                ->get();
        }

        return Inertia::render('PQRS/Edit', [
            'pqrs' => $pqrs,
            'apartments' => $apartments,
            'userApartment' => $user->apartment,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pqrs $pqrs)
    {
        $user = Auth::user();

        // Only allow editing by the submitter and only if still open
        if ($pqrs->submitted_by !== $user->id || !$pqrs->isOpen()) {
            abort(403, 'No puedes editar este PQRS.');
        }

        $request->validate([
            'type' => ['required', Rule::in(['peticion', 'queja', 'reclamo', 'sugerencia'])],
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => ['required', Rule::in(['baja', 'media', 'alta', 'urgente'])],
            'apartment_id' => 'nullable|exists:apartments,id',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $pqrs->update([
            'type' => $request->type,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'apartment_id' => $request->apartment_id ?? $user->apartment?->id,
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
        ]);

        // Handle new file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('pqrs/attachments', $filename, 'public');

                PqrsAttachment::create([
                    'pqrs_id' => $pqrs->id,
                    'filename' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'file_path' => $path,
                    'type' => $this->determineAttachmentType($file),
                    'uploaded_by' => $user->id,
                ]);
            }
        }

        return redirect()->route('pqrs.show', $pqrs)->with('success', 'PQRS actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pqrs $pqrs)
    {
        $user = Auth::user();

        // Only allow deletion by administrators or submitter (if still open)
        if (!$user->hasAnyRole(['admin_conjunto', 'superadmin']) && 
            ($pqrs->submitted_by !== $user->id || !$pqrs->isOpen())) {
            abort(403, 'No tienes autorización para eliminar este PQRS.');
        }

        // Delete associated files
        foreach ($pqrs->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $pqrs->delete();

        return redirect()->route('pqrs.index')->with('success', 'PQRS eliminado exitosamente');
    }

    /**
     * Assign PQRS to an administrator
     */
    public function assign(Request $request, Pqrs $pqrs)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            abort(403, 'No tienes autorización para asignar PQRS.');
        }

        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $pqrs->update([
            'assigned_to' => $request->assigned_to,
            'assigned_at' => $request->assigned_to ? now() : null,
            'status' => $request->assigned_to ? 'en_proceso' : 'abierto',
        ]);

        $message = $request->assigned_to ? 'PQRS asignado exitosamente' : 'Asignación de PQRS removida';
        return back()->with('success', $message);
    }

    /**
     * Update PQRS status and resolution
     */
    public function resolve(Request $request, Pqrs $pqrs)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            abort(403, 'No tienes autorización para resolver PQRS.');
        }

        $request->validate([
            'status' => ['required', Rule::in(['en_proceso', 'resuelto', 'cerrado'])],
            'resolution' => 'required_if:status,resuelto|string',
            'admin_notes' => 'nullable|string',
            'requires_follow_up' => 'boolean',
            'follow_up_date' => 'nullable|date|after:today',
            'follow_up_notes' => 'nullable|string',
        ]);

        $updateData = [
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'requires_follow_up' => $request->boolean('requires_follow_up'),
            'follow_up_date' => $request->follow_up_date,
            'follow_up_notes' => $request->follow_up_notes,
        ];

        if ($request->status === 'resuelto') {
            $updateData['resolution'] = $request->resolution;
            $updateData['resolved_at'] = now();
            $updateData['resolved_by'] = $user->id;
        }

        $pqrs->update($updateData);

        return back()->with('success', 'PQRS actualizado exitosamente');
    }

    /**
     * Rate a resolved PQRS
     */
    public function rate(Request $request, Pqrs $pqrs)
    {
        $user = Auth::user();

        if ($pqrs->submitted_by !== $user->id || !$pqrs->canBeRated()) {
            abort(403, 'No puedes calificar este PQRS.');
        }

        $request->validate([
            'satisfaction_rating' => 'required|integer|between:1,5',
            'satisfaction_comments' => 'nullable|string|max:500',
        ]);

        $pqrs->update([
            'satisfaction_rating' => $request->satisfaction_rating,
            'satisfaction_comments' => $request->satisfaction_comments,
            'satisfaction_submitted_at' => now(),
        ]);

        return back()->with('success', 'Calificación enviada exitosamente');
    }

    /**
     * Delete an attachment
     */
    public function deleteAttachment(PqrsAttachment $attachment)
    {
        $user = Auth::user();
        $pqrs = $attachment->pqrs;

        // Only allow deletion by administrators or submitter (if still open)
        if (!$user->hasAnyRole(['admin_conjunto', 'superadmin']) && 
            ($pqrs->submitted_by !== $user->id || !$pqrs->isOpen())) {
            abort(403, 'No tienes autorización para eliminar este archivo.');
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Archivo eliminado exitosamente');
    }

    /**
     * Determine attachment type based on file
     */
    private function determineAttachmentType($file): string
    {
        $mimeType = $file->getMimeType();
        
        if (str_starts_with($mimeType, 'image/')) {
            return 'photo';
        }
        
        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ])) {
            return 'document';
        }
        
        return 'evidence';
    }
}
