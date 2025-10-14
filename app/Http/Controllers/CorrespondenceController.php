<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCorrespondenceRequest;
use App\Http\Requests\UpdateCorrespondenceRequest;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Correspondence;
use App\Models\CorrespondenceAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Laravel\Pennant\Feature;

class CorrespondenceController extends Controller
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
        // Check if correspondence feature is enabled for this tenant
        // TODO: Re-enable when feature flags are properly configured in central API
        // if (! Feature::active('correspondence', function_exists('tenant') ? tenant('id') : 'default')) {
        //     return Inertia::render('FeatureDisabled', [
        //         'feature' => 'correspondence',
        //         'message' => 'El módulo de correspondencia no está disponible en su plan actual.',
        //         'upgrade_url' => route('subscription.plans'),
        //     ]);
        // }

        $query = Correspondence::with(['apartment', 'receivedBy', 'deliveredBy', 'attachments']);

        $user = Auth::user();

        // Role-based filtering
        if ($user->hasRole('residente') || $user->hasRole('propietario')) {
            // Residents and owners can only see correspondence for their apartment
            if ($user->apartment) {
                $query->where('apartment_id', $user->apartment->id);
            } else {
                // No apartment assigned, show no correspondence
                $query->whereNull('id');
            }
        }

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('tracking_number', 'like', '%'.$request->search.'%')
                    ->orWhere('sender_name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('apartment_id') && $user->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria'])) {
            $query->where('apartment_id', $request->apartment_id);
        }

        $correspondences = $query->orderBy('received_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $apartments = [];
        if ($user->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria'])) {
            $apartments = Apartment::orderBy('tower')->orderBy('floor')->orderBy('position_on_floor')->get();
        }

        return Inertia::render('Correspondence/Index', [
            'correspondences' => $correspondences,
            'apartments' => $apartments,
            'filters' => $request->only(['search', 'status', 'type', 'apartment_id']),
            'canCreate' => $user->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria']),
            'canManage' => $user->hasAnyRole(['admin_conjunto', 'superadmin']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Correspondence::class);

        $apartments = Apartment::with(['apartmentType', 'residents'])
            ->orderBy('tower')
            ->orderBy('floor')
            ->orderBy('position_on_floor')
            ->get();

        return Inertia::render('Correspondence/Create', [
            'apartments' => $apartments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCorrespondenceRequest $request)
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $correspondence = Correspondence::create([
            'conjunto_config_id' => $conjuntoConfig->id,
            'sender_name' => $request->sender_name,
            'sender_company' => $request->sender_company,
            'type' => $request->type,
            'description' => $request->description,
            'apartment_id' => $request->apartment_id,
            'status' => 'received',
            'received_by' => Auth::id(),
            'received_at' => now(),
            'requires_signature' => $request->boolean('requires_signature'),
        ]);

        // Handle photo attachments
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = time().'_'.uniqid().'.'.$photo->getClientOriginalExtension();
                $path = $photo->storeAs('correspondence/photos', $filename, 'public');

                CorrespondenceAttachment::create([
                    'correspondence_id' => $correspondence->id,
                    'filename' => $filename,
                    'original_filename' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType(),
                    'file_size' => $photo->getSize(),
                    'file_path' => $path,
                    'type' => 'photo_evidence',
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        return redirect()->route('correspondence.index')->with('success', 'Correspondencia registrada exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Correspondence $correspondence)
    {
        $this->authorize('view', $correspondence);

        $correspondence->load(['apartment.apartmentType', 'receivedBy', 'deliveredBy', 'attachments.uploadedBy']);

        return Inertia::render('Correspondence/Show', [
            'correspondence' => $correspondence,
            'canEdit' => Auth::user()->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria']),
            'canDeliver' => Auth::user()->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria']) && $correspondence->status === 'received',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Correspondence $correspondence)
    {
        $this->authorize('update', $correspondence);

        $correspondence->load(['attachments']);
        $apartments = Apartment::with(['apartmentType', 'residents'])
            ->orderBy('tower')
            ->orderBy('floor')
            ->orderBy('position_on_floor')
            ->get();

        return Inertia::render('Correspondence/Edit', [
            'correspondence' => $correspondence,
            'apartments' => $apartments,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCorrespondenceRequest $request, Correspondence $correspondence)
    {
        $correspondence->update([
            'sender_name' => $request->sender_name,
            'sender_company' => $request->sender_company,
            'type' => $request->type,
            'description' => $request->description,
            'apartment_id' => $request->apartment_id,
            'requires_signature' => $request->boolean('requires_signature'),
        ]);

        // Handle new photo attachments
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = time().'_'.uniqid().'.'.$photo->getClientOriginalExtension();
                $path = $photo->storeAs('correspondence/photos', $filename, 'public');

                CorrespondenceAttachment::create([
                    'correspondence_id' => $correspondence->id,
                    'filename' => $filename,
                    'original_filename' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType(),
                    'file_size' => $photo->getSize(),
                    'file_path' => $path,
                    'type' => 'photo_evidence',
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        return redirect()->route('correspondence.show', $correspondence)->with('success', 'Correspondencia actualizada exitosamente');
    }

    /**
     * Mark correspondence as delivered and optionally handle signature
     */
    public function markAsDelivered(Request $request, Correspondence $correspondence)
    {
        $this->authorize('deliver', $correspondence);

        $request->validate([
            'delivery_notes' => 'nullable|string|max:500',
            'recipient_name' => 'required_if:requires_signature,true|string|max:255',
            'recipient_document' => 'nullable|string|max:50',
            'signature' => 'required_if:requires_signature,true|image|max:2048',
        ]);

        $updateData = [
            'status' => 'delivered',
            'delivered_by' => Auth::id(),
            'delivered_at' => now(),
            'delivery_notes' => $request->delivery_notes,
        ];

        if ($correspondence->requires_signature) {
            $updateData['recipient_name'] = $request->recipient_name;
            $updateData['recipient_document'] = $request->recipient_document;

            // Handle signature upload
            if ($request->hasFile('signature')) {
                $signature = $request->file('signature');
                $filename = 'signature_'.time().'.'.$signature->getClientOriginalExtension();
                $path = $signature->storeAs('correspondence/signatures', $filename, 'public');

                $updateData['signature_path'] = $path;

                // Create attachment record for signature
                CorrespondenceAttachment::create([
                    'correspondence_id' => $correspondence->id,
                    'filename' => $filename,
                    'original_filename' => $signature->getClientOriginalName(),
                    'mime_type' => $signature->getMimeType(),
                    'file_size' => $signature->getSize(),
                    'file_path' => $path,
                    'type' => 'signature',
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        $correspondence->update($updateData);

        return back()->with('success', 'Correspondencia marcada como entregada');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Correspondence $correspondence)
    {
        $this->authorize('delete', $correspondence);

        // Delete associated files
        foreach ($correspondence->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $correspondence->delete();

        return redirect()->route('correspondence.index')->with('success', 'Correspondencia eliminada exitosamente');
    }

    /**
     * Delete an attachment
     */
    public function deleteAttachment(CorrespondenceAttachment $attachment)
    {
        $this->authorize('update', $attachment->correspondence);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Archivo eliminado exitosamente');
    }
}
