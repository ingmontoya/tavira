<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceStaff;
use App\Models\Provider;
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
        $this->middleware('permission:edit_maintenance_requests')->only(['nextState']);
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
            'provider',
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

        // Check if categories are configured
        $categories = MaintenanceCategory::where('conjunto_config_id', $conjuntoConfig->id)
            ->where('is_active', true)
            ->get();

        $hasCategoriesConfigured = $categories->count() > 0;

        // Check if staff is configured (optional)
        $hasStaffConfigured = MaintenanceStaff::where('conjunto_config_id', $conjuntoConfig->id)
            ->where('is_active', true)
            ->count() > 0;

        // Calculate metrics/indicators
        $baseQuery = MaintenanceRequest::where('conjunto_config_id', $conjuntoConfig->id);

        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereIn('status', ['created', 'evaluation', 'budgeted', 'pending_approval', 'approved', 'assigned', 'in_progress'])->count(),
            'completed_this_month' => (clone $baseQuery)->where('status', 'completed')->whereMonth('updated_at', now()->month)->count(),
            'critical_priority' => (clone $baseQuery)->where('priority', 'critical')->whereNotIn('status', ['completed', 'closed'])->count(),
            'pending_approval' => (clone $baseQuery)->where('status', 'pending_approval')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'recurring_active' => (clone $baseQuery)->where('is_recurring', true)->where('is_recurring_paused', false)->count(),
            'recurring_paused' => (clone $baseQuery)->where('is_recurring', true)->where('is_recurring_paused', true)->count(),
            'upcoming_recurring' => MaintenanceRequest::recurringDueSoon(30)->count(),
            'total_cost_this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->sum('estimated_cost'),
        ];

        return Inertia::render('Maintenance/Requests/Index', [
            'maintenanceRequests' => $maintenanceRequests,
            'metrics' => $metrics,
            'filters' => $request->only(['status', 'priority', 'category', 'search']),
            'categories' => $categories,
            'hasCategoriesConfigured' => $hasCategoriesConfigured,
            'hasStaffConfigured' => $hasStaffConfigured,
            'needsSetup' => ! $hasCategoriesConfigured,
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
            ->where(function ($q) {
                // Include requests that have estimated_completion_date OR next_occurrence_date
                $q->whereNotNull('estimated_completion_date')
                    ->orWhereNotNull('next_occurrence_date');
            });

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $maintenanceRequests = $query->orderBy('estimated_completion_date')->get();

        $filterStartDate = $request->filled('start') ? \Carbon\Carbon::parse($request->start) : null;
        $filterEndDate = $request->filled('end') ? \Carbon\Carbon::parse($request->end) : null;

        // Transform data for calendar view, generating multiple occurrences for recurring events
        $calendarEvents = collect();

        foreach ($maintenanceRequests as $maintenanceRequest) {
            if ($maintenanceRequest->is_recurring && $maintenanceRequest->recurrence_start_date) {
                // Generate all occurrences within the filter range (or next 12 months if no filter)
                $occurrences = $this->generateRecurringOccurrences(
                    $maintenanceRequest,
                    $filterStartDate,
                    $filterEndDate
                );

                foreach ($occurrences as $occurrenceDate) {
                    $calendarEvents->push([
                        'id' => $maintenanceRequest->id,
                        'title' => $maintenanceRequest->title,
                        'start' => $occurrenceDate->format('Y-m-d'),
                        'end' => $maintenanceRequest->actual_completion_date?->format('Y-m-d'),
                        'status' => $maintenanceRequest->status,
                        'priority' => $maintenanceRequest->priority,
                        'category' => $maintenanceRequest->maintenanceCategory->name,
                        'categoryColor' => $maintenanceRequest->maintenanceCategory->color,
                        'url' => route('maintenance-requests.show', $maintenanceRequest->id),
                        'backgroundColor' => $this->getEventBackgroundColor($maintenanceRequest->maintenanceCategory->color, $maintenanceRequest->priority, $maintenanceRequest->status),
                        'borderColor' => $this->getEventBorderColor($maintenanceRequest->priority, $maintenanceRequest->status),
                        'isRecurring' => true,
                        'recurrenceFrequency' => $maintenanceRequest->getRecurrenceFrequencyLabel(),
                    ]);
                }
            } else {
                // Non-recurring event - only add if it's within the date range
                $eventDate = $maintenanceRequest->estimated_completion_date;
                if ($eventDate) {
                    $includeEvent = true;
                    if ($filterStartDate && $filterEndDate) {
                        $includeEvent = $eventDate >= $filterStartDate && $eventDate <= $filterEndDate;
                    }

                    if ($includeEvent) {
                        $calendarEvents->push([
                            'id' => $maintenanceRequest->id,
                            'title' => $maintenanceRequest->title,
                            'start' => $eventDate->format('Y-m-d'),
                            'end' => $maintenanceRequest->actual_completion_date?->format('Y-m-d'),
                            'status' => $maintenanceRequest->status,
                            'priority' => $maintenanceRequest->priority,
                            'category' => $maintenanceRequest->maintenanceCategory->name,
                            'categoryColor' => $maintenanceRequest->maintenanceCategory->color,
                            'url' => route('maintenance-requests.show', $maintenanceRequest->id),
                            'backgroundColor' => $this->getEventBackgroundColor($maintenanceRequest->maintenanceCategory->color, $maintenanceRequest->priority, $maintenanceRequest->status),
                            'borderColor' => $this->getEventBorderColor($maintenanceRequest->priority, $maintenanceRequest->status),
                            'isRecurring' => false,
                            'recurrenceFrequency' => null,
                        ]);
                    }
                }
            }
        }

        \Log::info('Calendar query parameters', [
            'start_date' => $request->start ?? 'not provided',
            'end_date' => $request->end ?? 'not provided',
            'status_filter' => $request->status ?? 'not provided',
        ]);

        \Log::info('Calendar events being sent to frontend', [
            'total_requests' => $maintenanceRequests->count(),
            'total_events' => $calendarEvents->count(),
            'events' => $calendarEvents->toArray(),
        ]);

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

        $categories = MaintenanceCategory::where('conjunto_config_id', $conjuntoConfig->id)
            ->where('is_active', true)
            ->get();

        $hasCategoriesConfigured = $categories->count() > 0;

        // Check if staff is configured (optional)
        $hasStaffConfigured = MaintenanceStaff::where('conjunto_config_id', $conjuntoConfig->id)
            ->where('is_active', true)
            ->count() > 0;

        // If no categories exist, redirect to wizard
        if (! $hasCategoriesConfigured) {
            return Inertia::render('Maintenance/Requests/Create', [
                'categories' => $categories,
                'apartments' => [],
                'providers' => [],
                'hasCategoriesConfigured' => false,
                'hasStaffConfigured' => $hasStaffConfigured,
                'needsSetup' => true,
            ]);
        }

        return Inertia::render('Maintenance/Requests/Create', [
            'categories' => $categories,
            'apartments' => Apartment::where('conjunto_config_id', $conjuntoConfig->id)
                ->with('apartmentType')
                ->orderBy('number')
                ->get(),
            'providers' => Provider::active()
                ->orderBy('name')
                ->get(),
            'hasCategoriesConfigured' => true,
            'hasStaffConfigured' => $hasStaffConfigured,
            'needsSetup' => false,
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
            'project_type' => 'required|in:internal,external',
            'status' => 'required|in:created,evaluation,budgeted,pending_approval,approved,assigned,in_progress,completed,closed,rejected,suspended',
            'location' => 'nullable|string|max:255',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_completion_date' => 'nullable|date|after:today',
            'requires_council_approval' => 'boolean',
            // Vendor fields (optional)
            'provider_id' => 'nullable|exists:providers,id',
            'vendor_quote_amount' => 'nullable|numeric|min:0',
            'vendor_quote_description' => 'nullable|string',
            'vendor_quote_valid_until' => 'nullable|date|after:today',
            'vendor_contact_name' => 'nullable|string|max:255',
            'vendor_contact_phone' => 'nullable|string|max:20',
            'vendor_contact_email' => 'nullable|email|max:255',
            // Recurrence fields
            'is_recurring' => 'boolean',
            'recurrence_frequency' => 'nullable|required_if:is_recurring,true|in:daily,weekly,monthly,quarterly,semi_annual,annual',
            'recurrence_interval' => 'nullable|integer|min:1|max:100',
            'recurrence_start_date' => 'nullable|required_if:is_recurring,true|date|after_or_equal:today',
            'recurrence_end_date' => 'nullable|date|after:recurrence_start_date',
            'days_before_notification' => 'nullable|integer|min:1|max:30',
        ]);

        $validated['conjunto_config_id'] = $conjuntoConfig->id;
        $validated['requested_by_user_id'] = Auth::id();

        // Debug logging
        \Log::info('Creating maintenance request', [
            'is_recurring' => $validated['is_recurring'] ?? 'not set',
            'recurrence_frequency' => $validated['recurrence_frequency'] ?? 'not set',
            'recurrence_start_date' => $validated['recurrence_start_date'] ?? 'not set',
            'all_validated' => $validated,
        ]);

        // Calculate next occurrence date if recurring
        if ($validated['is_recurring'] ?? false) {
            $validated['next_occurrence_date'] = $validated['recurrence_start_date'];
            \Log::info('Setting next_occurrence_date', ['date' => $validated['next_occurrence_date']]);
        }

        $maintenanceRequest = MaintenanceRequest::create($validated);

        \Log::info('Maintenance request created', [
            'id' => $maintenanceRequest->id,
            'is_recurring' => $maintenanceRequest->is_recurring,
            'next_occurrence_date' => $maintenanceRequest->next_occurrence_date,
        ]);
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
            'documents.uploadedBy',
        ]);

        // Add recurrence frequency label if recurring
        $maintenanceRequestArray = $maintenanceRequest->toArray();
        if ($maintenanceRequest->is_recurring) {
            $maintenanceRequestArray['recurrence_frequency_label'] = $maintenanceRequest->getRecurrenceFrequencyLabel();
        }

        return Inertia::render('Maintenance/Requests/Show', [
            'maintenanceRequest' => $maintenanceRequestArray,
            'staff' => MaintenanceStaff::where('conjunto_config_id', $maintenanceRequest->conjunto_config_id)
                ->where('is_active', true)
                ->get(),
            'permissions' => [
                'can_approve' => Auth::user()->can('approve_maintenance_requests'),
                'can_assign' => Auth::user()->can('assign_maintenance_requests'),
                'can_complete' => Auth::user()->can('complete_maintenance_requests'),
                'can_edit' => Auth::user()->can('edit_maintenance_requests'),
                'can_delete' => Auth::user()->can('delete_maintenance_requests'),
            ],
            'nextState' => [
                'can_transition' => $maintenanceRequest->canTransitionToNextState(),
                'next_status' => $maintenanceRequest->getNextStatus(),
                'next_status_label' => $maintenanceRequest->getNextStatusLabel(),
            ],
        ]);
    }

    public function edit(MaintenanceRequest $maintenanceRequest): Response
    {
        $conjuntoConfig = ConjuntoConfig::first();

        return Inertia::render('Maintenance/Requests/Edit', [
            'maintenanceRequest' => $maintenanceRequest->load([
                'maintenanceCategory',
                'apartment',
                'provider',
            ]),
            'categories' => MaintenanceCategory::where('conjunto_config_id', $conjuntoConfig->id)
                ->where('is_active', true)
                ->get(),
            'apartments' => Apartment::where('conjunto_config_id', $conjuntoConfig->id)
                ->with('apartmentType')
                ->orderBy('number')
                ->get(),
            'providers' => Provider::active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        \Log::info('Update request received', [
            'request_id' => $maintenanceRequest->id,
            'is_recurring_in_request' => $request->input('is_recurring'),
            'all_request_data' => $request->all(),
        ]);

        $validated = $request->validate([
            'maintenance_category_id' => 'required|exists:maintenance_categories,id',
            'apartment_id' => 'nullable|exists:apartments,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'project_type' => 'required|in:internal,external',
            'status' => 'required|in:created,evaluation,budgeted,pending_approval,approved,assigned,in_progress,completed,closed,rejected,suspended',
            'location' => 'nullable|string|max:255',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_completion_date' => 'nullable|date',
            'requires_council_approval' => 'boolean',
            // Vendor fields (optional)
            'provider_id' => 'nullable|exists:providers,id',
            'vendor_quote_amount' => 'nullable|numeric|min:0',
            'vendor_quote_description' => 'nullable|string',
            'vendor_quote_valid_until' => 'nullable|date',
            'vendor_contact_name' => 'nullable|string|max:255',
            'vendor_contact_phone' => 'nullable|string|max:20',
            'vendor_contact_email' => 'nullable|email|max:255',
            // Recurrence fields
            'is_recurring' => 'boolean',
            'recurrence_frequency' => 'nullable|required_if:is_recurring,true|in:daily,weekly,monthly,quarterly,semi_annual,annual',
            'recurrence_interval' => 'nullable|integer|min:1|max:100',
            'recurrence_start_date' => 'nullable|required_if:is_recurring,true|date',
            'recurrence_end_date' => 'nullable|date|after:recurrence_start_date',
            'days_before_notification' => 'nullable|integer|min:1|max:30',
        ]);

        \Log::info('After validation', [
            'is_recurring_validated' => $validated['is_recurring'] ?? 'not present',
            'validated_data' => $validated,
        ]);

        // Update next_occurrence_date if recurring settings changed
        if ($validated['is_recurring'] ?? false) {
            $validated['next_occurrence_date'] = $validated['recurrence_start_date'];
        } else {
            // Clear recurrence data if is_recurring is false
            $validated['next_occurrence_date'] = null;
        }

        \Log::info('Before update', [
            'final_data_to_update' => $validated,
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

    public function nextState(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        if (! $maintenanceRequest->canTransitionToNextState()) {
            return back()->with('error', 'Esta solicitud no puede avanzar al siguiente estado.');
        }

        $nextStatus = $maintenanceRequest->getNextStatus();
        $nextStatusLabel = $maintenanceRequest->getNextStatusLabel();

        // Handle special cases that require additional fields
        if ($nextStatus === MaintenanceRequest::STATUS_APPROVED) {
            $maintenanceRequest->update([
                'status' => $nextStatus,
                'approved_by_user_id' => Auth::id(),
                'council_approved_at' => $maintenanceRequest->requires_council_approval ? now() : null,
            ]);
        } elseif ($nextStatus === MaintenanceRequest::STATUS_COMPLETED) {
            $maintenanceRequest->update([
                'status' => $nextStatus,
                'actual_completion_date' => now(),
            ]);
        } else {
            $maintenanceRequest->update([
                'status' => $nextStatus,
            ]);
        }

        return back()->with('success', "Solicitud avanzada a: {$nextStatusLabel}");
    }

    /**
     * Get maintenance schedule data for mobile app (resident view)
     */
    public function apiResidentIndex(Request $request)
    {
        $user = Auth::user();
        $conjuntoConfig = ConjuntoConfig::first();

        // Get resident's apartment
        $resident = $user->resident;
        if (! $resident) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no tiene apartamento asignado',
            ], 400);
        }

        $query = MaintenanceRequest::with([
            'maintenanceCategory',
            'apartment',
            'requestedBy',
            'assignedStaff',
        ])->where('conjunto_config_id', $conjuntoConfig->id)
            ->where(function ($q) use ($resident) {
                // Show requests that affect the resident's apartment or are general building maintenance
                $q->where('apartment_id', $resident->apartment_id)
                    ->orWhereNull('apartment_id'); // General building maintenance
            });

        // Filter by status - show only active/relevant requests
        $query->whereIn('status', [
            'assigned',
            'in_progress',
            'approved',
            'pending_approval',
        ]);

        // Get upcoming maintenance (next 30 days)
        $query->where(function ($q) {
            $q->whereDate('estimated_completion_date', '>=', now())
                ->whereDate('estimated_completion_date', '<=', now()->addDays(30));
        });

        $maintenanceRequests = $query->orderBy('estimated_completion_date')->limit(20)->get();

        // Transform data for mobile app
        $scheduleData = $maintenanceRequests->map(function ($request) {
            return [
                'id' => $request->id,
                'title' => $request->title,
                'description' => $request->description,
                'category' => [
                    'name' => $request->maintenanceCategory->name,
                    'color' => $request->maintenanceCategory->color,
                ],
                'priority' => $request->priority,
                'priority_label' => $this->getPriorityLabel($request->priority),
                'status' => $request->status,
                'status_label' => $this->getStatusLabel($request->status),
                'estimated_date' => $request->estimated_completion_date?->format('Y-m-d'),
                'estimated_date_formatted' => $request->estimated_completion_date?->format('d M Y'),
                'location' => $request->location,
                'apartment' => $request->apartment ? [
                    'number' => $request->apartment->number,
                    'floor' => $request->apartment->floor,
                ] : null,
                'is_general_maintenance' => is_null($request->apartment_id),
                'assigned_staff' => $request->assignedStaff ? [
                    'name' => $request->assignedStaff->name,
                    'phone' => $request->assignedStaff->phone,
                ] : null,
                'affects_resident' => $request->apartment_id === $resident->apartment_id,
            ];
        });

        // Get maintenance categories for filtering
        $categories = MaintenanceCategory::where('conjunto_config_id', $conjuntoConfig->id)
            ->where('is_active', true)
            ->select('id', 'name', 'color')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'schedule' => $scheduleData,
                'categories' => $categories,
                'summary' => [
                    'total_requests' => $scheduleData->count(),
                    'personal_requests' => $scheduleData->where('affects_resident', true)->count(),
                    'general_maintenance' => $scheduleData->where('is_general_maintenance', true)->count(),
                    'next_maintenance_date' => $scheduleData->first()['estimated_date_formatted'] ?? null,
                ],
            ],
        ]);
    }

    /**
     * Get priority label in Spanish
     */
    private function getPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'critical' => 'Crítica',
            default => 'Media',
        };
    }

    /**
     * Get status label in Spanish
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'created' => 'Creada',
            'evaluation' => 'En Evaluación',
            'budgeted' => 'Presupuestada',
            'pending_approval' => 'Pendiente Aprobación',
            'approved' => 'Aprobada',
            'assigned' => 'Asignada',
            'in_progress' => 'En Progreso',
            'completed' => 'Completada',
            'closed' => 'Cerrada',
            'rejected' => 'Rechazada',
            'suspended' => 'Suspendida',
            default => 'Desconocido',
        };
    }

    /**
     * Pause recurrence for a maintenance request
     */
    public function pauseRecurrence(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if (! $maintenanceRequest->is_recurring) {
            return redirect()->back()->with('error', 'Esta solicitud no es recurrente.');
        }

        if ($maintenanceRequest->is_recurring_paused) {
            return redirect()->back()->with('warning', 'La recurrencia ya está pausada.');
        }

        $maintenanceRequest->pauseRecurrence($validated['reason'] ?? null);

        return redirect()->back()->with('success', 'Recurrencia pausada exitosamente.');
    }

    /**
     * Resume recurrence for a maintenance request
     */
    public function resumeRecurrence(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        if (! $maintenanceRequest->is_recurring) {
            return redirect()->back()->with('error', 'Esta solicitud no es recurrente.');
        }

        if (! $maintenanceRequest->is_recurring_paused) {
            return redirect()->back()->with('warning', 'La recurrencia no está pausada.');
        }

        $maintenanceRequest->resumeRecurrence();

        return redirect()->back()->with('success', 'Recurrencia reactivada exitosamente.');
    }

    /**
     * Generate all occurrences of a recurring maintenance request within a date range
     */
    private function generateRecurringOccurrences(MaintenanceRequest $request, ?\Carbon\Carbon $rangeStart, ?\Carbon\Carbon $rangeEnd): array
    {
        $occurrences = [];

        // If no range provided, default to next 12 months
        if (! $rangeStart) {
            $rangeStart = now()->startOfMonth();
        }
        if (! $rangeEnd) {
            $rangeEnd = now()->addYear()->endOfMonth();
        }

        $currentDate = \Carbon\Carbon::parse($request->recurrence_start_date);
        $interval = $request->recurrence_interval ?? 1;
        $endDate = $request->recurrence_end_date ? \Carbon\Carbon::parse($request->recurrence_end_date) : null;

        // Limit to 100 occurrences to prevent infinite loops
        $maxOccurrences = 100;
        $count = 0;

        while ($count < $maxOccurrences) {
            // Stop if we've passed the recurrence end date
            if ($endDate && $currentDate > $endDate) {
                break;
            }

            // Stop if we've gone way past the range end date (optimization)
            if ($currentDate > $rangeEnd->copy()->addYear()) {
                break;
            }

            // Only include dates within the requested range
            if ($currentDate >= $rangeStart && $currentDate <= $rangeEnd) {
                $occurrences[] = $currentDate->copy();
            }

            // Calculate next occurrence based on frequency
            switch ($request->recurrence_frequency) {
                case 'daily':
                    $currentDate->addDays($interval);
                    break;
                case 'weekly':
                    $currentDate->addWeeks($interval);
                    break;
                case 'monthly':
                    $currentDate->addMonths($interval);
                    break;
                case 'quarterly':
                    $currentDate->addMonths(3 * $interval);
                    break;
                case 'semi_annual':
                    $currentDate->addMonths(6 * $interval);
                    break;
                case 'annual':
                    $currentDate->addYears($interval);
                    break;
                default:
                    // Unknown frequency, break the loop
                    break 2;
            }

            $count++;
        }

        return $occurrences;
    }
}
