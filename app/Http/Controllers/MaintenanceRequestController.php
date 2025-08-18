<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceStaff;
use App\Notifications\MaintenanceRequestCreated;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_maintenance_requests')->only(['index', 'show', 'calendar']);
        $this->middleware('permission:create_maintenance_requests')->only(['create', 'store']);
        $this->middleware('permission:edit_maintenance_requests')->only(['edit', 'update']);
        $this->middleware('permission:delete_maintenance_requests')->only(['destroy']);
        $this->middleware('permission:approve_maintenance_requests')->only(['approve']);
        $this->middleware('permission:assign_maintenance_requests')->only(['assign']);
        $this->middleware('permission:complete_maintenance_requests')->only(['startWork', 'complete']);
    }

    public function index(Request $request): Response
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $query = MaintenanceRequest::with([
            'maintenanceCategory',
            'apartment',
            'requestedBy',
            'assignedStaff',
            'approvedBy',
        ])->where('conjunto_config_id', $conjuntoConfig->id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('maintenance_category_id', $request->category);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $maintenanceRequests = $query->latest()->paginate(15);

        return Inertia::render('Maintenance/Requests/Index', [
            'maintenanceRequests' => $maintenanceRequests,
            'filters' => $request->only(['status', 'priority', 'category', 'search']),
            'categories' => MaintenanceCategory::where('conjunto_config_id', $conjuntoConfig->id)
                ->where('is_active', true)
                ->get(),
        ]);
    }

    public function calendar(Request $request): Response
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $query = MaintenanceRequest::with([
            'maintenanceCategory',
            'apartment',
            'requestedBy',
            'assignedStaff',
        ])->where('conjunto_config_id', $conjuntoConfig->id)
            ->whereNotNull('estimated_completion_date');

        // Filter by date range if provided
        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('estimated_completion_date', [
                $request->start,
                $request->end,
            ]);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $maintenanceRequests = $query->orderBy('estimated_completion_date')->get();

        // Transform data for calendar view
        $calendarEvents = $maintenanceRequests->map(function ($request) {
            return [
                'id' => $request->id,
                'title' => $request->title,
                'start' => $request->estimated_completion_date ? $request->estimated_completion_date->format('Y-m-d') : null,
                'end' => $request->actual_completion_date?->format('Y-m-d'),
                'status' => $request->status,
                'priority' => $request->priority,
                'category' => $request->maintenanceCategory->name,
                'categoryColor' => $request->maintenanceCategory->color,
                'url' => route('maintenance-requests.show', $request->id),
                'backgroundColor' => $this->getEventBackgroundColor($request->maintenanceCategory->color, $request->priority, $request->status),
                'borderColor' => $this->getEventBorderColor($request->priority, $request->status),
            ];
        })->filter(function ($event) {
            return $event['start'] !== null;
        });

        return Inertia::render('Maintenance/Requests/Calendar', [
            'events' => $calendarEvents,
            'requests' => $maintenanceRequests,
            'filters' => $request->only(['status', 'start', 'end']),
        ]);
    }

    private function getEventBackgroundColor(string $categoryColor, string $priority, string $status): string
    {
        // Use category color as base, but adjust opacity based on status
        $opacity = match ($status) {
            'created', 'evaluation' => '0.6',
            'budgeted', 'pending_approval' => '0.7',
            'approved', 'assigned' => '0.8',
            'in_progress' => '0.9',
            'completed' => '0.95',
            'closed' => '0.5',
            'rejected', 'suspended' => '0.4',
            default => '0.8',
        };

        // For critical/high priority, use a more saturated version
        if ($priority === 'critical' || $priority === 'high') {
            $opacity = '1.0';
        }

        // Convert hex to rgba for opacity
        return $this->hexToRgba($categoryColor, (float) $opacity);
    }

    private function getEventBorderColor(string $priority, string $status): string
    {
        // Border color indicates priority and urgency
        if ($priority === 'critical') {
            return '#dc2626'; // red-600
        }
        if ($priority === 'high') {
            return '#ea580c'; // orange-600
        }

        // Status-based border for normal/low priority
        return match ($status) {
            'created', 'evaluation' => '#6b7280', // gray-500
            'budgeted', 'pending_approval' => '#d97706', // amber-600
            'approved', 'assigned' => '#2563eb', // blue-600
            'in_progress' => '#059669', // emerald-600
            'completed' => '#16a34a', // green-600
            'closed' => '#4b5563', // gray-600
            'rejected', 'suspended' => '#dc2626', // red-600
            default => '#6b7280', // gray-500
        };
    }

    private function hexToRgba(string $hex, float $opacity): string
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert hex to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba($r, $g, $b, $opacity)";
    }

    public function create(): Response
    {
        $conjuntoConfig = ConjuntoConfig::first();

        return Inertia::render('Maintenance/Requests/Create', [
            'categories' => MaintenanceCategory::where('conjunto_config_id', $conjuntoConfig->id)
                ->where('is_active', true)
                ->get(),
            'apartments' => Apartment::where('conjunto_config_id', $conjuntoConfig->id)
                ->with('apartmentType')
                ->orderBy('number')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $validated = $request->validate([
            'maintenance_category_id' => 'required|exists:maintenance_categories,id',
            'apartment_id' => 'nullable|exists:apartments,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'location' => 'nullable|string|max:255',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_completion_date' => 'nullable|date|after:today',
            'requires_council_approval' => 'boolean',
        ]);

        $validated['conjunto_config_id'] = $conjuntoConfig->id;
        $validated['requested_by_user_id'] = Auth::id();
        $validated['status'] = MaintenanceRequest::STATUS_CREATED;

        $maintenanceRequest = MaintenanceRequest::create($validated);
        $maintenanceRequest->load(['maintenanceCategory', 'requestedBy']);

        // Send notification to administrative users
        $notificationService = app(NotificationService::class);
        $notificationService->notifyAdministrative(new MaintenanceRequestCreated($maintenanceRequest));

        return redirect()->route('maintenance-requests.index')
            ->with('success', 'Solicitud de mantenimiento creada exitosamente.');
    }

    public function show(MaintenanceRequest $maintenanceRequest): Response
    {
        $maintenanceRequest->load([
            'maintenanceCategory',
            'apartment.apartmentType',
            'requestedBy',
            'assignedStaff',
            'approvedBy',
            'workOrders.assignedStaff',
        ]);

        return Inertia::render('Maintenance/Requests/Show', [
            'maintenanceRequest' => $maintenanceRequest,
            'staff' => MaintenanceStaff::where('conjunto_config_id', $maintenanceRequest->conjunto_config_id)
                ->where('is_active', true)
                ->get(),
        ]);
    }

    public function edit(MaintenanceRequest $maintenanceRequest): Response
    {
        $conjuntoConfig = ConjuntoConfig::first();

        return Inertia::render('Maintenance/Requests/Edit', [
            'maintenanceRequest' => $maintenanceRequest->load([
                'maintenanceCategory',
                'apartment',
            ]),
            'categories' => MaintenanceCategory::where('conjunto_config_id', $conjuntoConfig->id)
                ->where('is_active', true)
                ->get(),
            'apartments' => Apartment::where('conjunto_config_id', $conjuntoConfig->id)
                ->with('apartmentType')
                ->orderBy('number')
                ->get(),
        ]);
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'maintenance_category_id' => 'required|exists:maintenance_categories,id',
            'apartment_id' => 'nullable|exists:apartments,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'location' => 'nullable|string|max:255',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_completion_date' => 'nullable|date',
            'requires_council_approval' => 'boolean',
        ]);

        $maintenanceRequest->update($validated);

        return redirect()->route('maintenance-requests.show', $maintenanceRequest)
            ->with('success', 'Solicitud de mantenimiento actualizada exitosamente.');
    }

    public function destroy(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $maintenanceRequest->delete();

        return redirect()->route('maintenance-requests.index')
            ->with('success', 'Solicitud de mantenimiento eliminada exitosamente.');
    }

    public function approve(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        if (! $maintenanceRequest->canBeApproved()) {
            return back()->with('error', 'Esta solicitud no puede ser aprobada en su estado actual.');
        }

        $maintenanceRequest->update([
            'status' => MaintenanceRequest::STATUS_APPROVED,
            'approved_by_user_id' => Auth::id(),
            'council_approved_at' => $maintenanceRequest->requires_council_approval ? now() : null,
        ]);

        return back()->with('success', 'Solicitud aprobada exitosamente.');
    }

    public function assign(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_staff_id' => 'required|exists:maintenance_staff,id',
        ]);

        if (! $maintenanceRequest->canBeAssigned()) {
            return back()->with('error', 'Esta solicitud no puede ser asignada en su estado actual.');
        }

        $maintenanceRequest->update([
            'assigned_staff_id' => $validated['assigned_staff_id'],
            'status' => MaintenanceRequest::STATUS_ASSIGNED,
        ]);

        return back()->with('success', 'Solicitud asignada exitosamente.');
    }

    public function startWork(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        if (! $maintenanceRequest->canStartWork()) {
            return back()->with('error', 'No se puede iniciar el trabajo en esta solicitud.');
        }

        $maintenanceRequest->update([
            'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
        ]);

        return back()->with('success', 'Trabajo iniciado exitosamente.');
    }

    public function complete(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        if (! $maintenanceRequest->canBeCompleted()) {
            return back()->with('error', 'Esta solicitud no puede ser completada en su estado actual.');
        }

        $maintenanceRequest->update([
            'status' => MaintenanceRequest::STATUS_COMPLETED,
            'actual_completion_date' => now(),
        ]);

        return back()->with('success', 'Solicitud completada exitosamente.');
    }
}
