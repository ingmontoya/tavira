<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservableAssetRequest;
use App\Http\Requests\UpdateReservableAssetRequest;
use App\Models\ConjuntoConfig;
use App\Models\ReservableAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReservableAssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $conjuntoConfig) {
            return redirect()->route('conjunto-config.create')
                ->with('error', 'Debe configurar el conjunto antes de gestionar activos.');
        }

        $query = ReservableAsset::where('conjunto_config_id', $conjuntoConfig->id)
            ->withCount(['reservations', 'activeReservations']);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $assets = $query->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // Get available asset types for filter
        $assetTypes = [
            'common_area' => 'Área Común',
            'amenity' => 'Amenidad',
            'facility' => 'Instalación',
            'sports' => 'Deportivo',
            'recreation' => 'Recreativo',
            'meeting_room' => 'Sala de Reuniones',
            'event_space' => 'Espacio para Eventos',
        ];

        return Inertia::render('ReservableAssets/Index', [
            'assets' => $assets,
            'assetTypes' => $assetTypes,
            'filters' => $request->only(['search', 'type', 'is_active']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $conjuntoConfig) {
            return redirect()->route('conjunto-config.create')
                ->with('error', 'Debe configurar el conjunto antes de crear activos.');
        }

        $assetTypes = [
            'common_area' => 'Área Común',
            'amenity' => 'Amenidad',
            'facility' => 'Instalación',
            'sports' => 'Deportivo',
            'recreation' => 'Recreativo',
            'meeting_room' => 'Sala de Reuniones',
            'event_space' => 'Espacio para Eventos',
        ];

        return Inertia::render('ReservableAssets/Create', [
            'conjuntoConfig' => $conjuntoConfig,
            'assetTypes' => $assetTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservableAssetRequest $request)
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('reservable-assets', 'public');
            $data['image_path'] = $imagePath;
        }

        ReservableAsset::create($data);

        return redirect()->route('reservable-assets.index')
            ->with('success', 'Activo reservable creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ReservableAsset $reservableAsset)
    {
        $reservableAsset->load([
            'conjuntoConfig',
            'reservations' => function ($query) {
                $query->with(['user', 'apartment'])
                    ->latest('start_time');
            },
        ]);

        // Get reservation statistics
        $totalReservations = $reservableAsset->reservations()->count();
        $activeReservations = $reservableAsset->activeReservations()->count();
        $pendingApproval = $reservableAsset->reservations()
            ->where('status', 'pending')
            ->count();
        $completedReservations = $reservableAsset->reservations()
            ->where('status', 'completed')
            ->count();

        $stats = [
            'total_reservations' => $totalReservations,
            'active_reservations' => $activeReservations,
            'pending_approval' => $pendingApproval,
            'completed_reservations' => $completedReservations,
        ];

        return Inertia::render('ReservableAssets/Show', [
            'asset' => $reservableAsset,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReservableAsset $reservableAsset)
    {
        $assetTypes = [
            'common_area' => 'Área Común',
            'amenity' => 'Amenidad',
            'facility' => 'Instalación',
            'sports' => 'Deportivo',
            'recreation' => 'Recreativo',
            'meeting_room' => 'Sala de Reuniones',
            'event_space' => 'Espacio para Eventos',
        ];

        return Inertia::render('ReservableAssets/Edit', [
            'asset' => $reservableAsset,
            'assetTypes' => $assetTypes,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReservableAssetRequest $request, ReservableAsset $reservableAsset)
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($reservableAsset->image_path && Storage::disk('public')->exists($reservableAsset->image_path)) {
                Storage::disk('public')->delete($reservableAsset->image_path);
            }

            $imagePath = $request->file('image')->store('reservable-assets', 'public');
            $data['image_path'] = $imagePath;
        }

        $reservableAsset->update($data);

        return redirect()->route('reservable-assets.index')
            ->with('success', 'Activo reservable actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReservableAsset $reservableAsset)
    {
        // Check if there are any active reservations
        if ($reservableAsset->activeReservations()->exists()) {
            return back()->with('error', 'No se puede eliminar el activo porque tiene reservas activas.');
        }

        // Delete image if exists
        if ($reservableAsset->image_path && Storage::disk('public')->exists($reservableAsset->image_path)) {
            Storage::disk('public')->delete($reservableAsset->image_path);
        }

        $reservableAsset->delete();

        return redirect()->route('reservable-assets.index')
            ->with('success', 'Activo reservable eliminado exitosamente.');
    }
}
