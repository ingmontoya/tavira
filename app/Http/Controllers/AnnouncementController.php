<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Announcement::with(['createdBy', 'updatedBy']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('pinned')) {
            $query->where('is_pinned', $request->boolean('pinned'));
        }

        $announcements = $query->orderByDesc('is_pinned')
            ->orderBy(\DB::raw("CASE 
                WHEN priority = 'urgent' THEN 1 
                WHEN priority = 'important' THEN 2 
                WHEN priority = 'normal' THEN 3 
                ELSE 4 
            END"))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('announcements/Index', [
            'announcements' => $announcements,
            'filters' => $request->only(['search', 'status', 'priority', 'type', 'pinned']),
            'stats' => [
                'total' => Announcement::count(),
                'published' => Announcement::published()->count(),
                'draft' => Announcement::draft()->count(),
                'urgent' => Announcement::byPriority('urgent')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('announcements/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', 'in:urgent,important,normal'],
            'type' => ['required', 'in:general,administrative,maintenance,emergency'],
            'status' => ['required', 'in:draft,published,archived'],
            'is_pinned' => ['required', 'boolean'],
            'requires_confirmation' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['array'],
            'attachments.*.name' => ['required', 'string'],
            'attachments.*.url' => ['required', 'string'],
            'attachments.*.size' => ['nullable', 'integer'],
        ]);

        $validated['created_by'] = Auth::id();

        if ($validated['status'] === 'published' && ! isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $announcement = Announcement::create($validated);

        $message = $validated['status'] === 'published'
            ? 'Anuncio publicado exitosamente.'
            : 'Anuncio guardado como borrador.';

        return redirect()->route('announcements.index')->with('success', $message);
    }

    public function show(Announcement $announcement): Response
    {
        $announcement->load(['createdBy', 'updatedBy']);

        // Get confirmation stats if requires confirmation
        $confirmationStats = null;
        if ($announcement->requires_confirmation) {
            $totalUsers = User::count();
            $confirmations = $announcement->confirmations()
                ->selectRaw('
                    COUNT(*) as total_interactions,
                    SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read_count,
                    SUM(CASE WHEN confirmed_at IS NOT NULL THEN 1 ELSE 0 END) as confirmed_count
                ')
                ->first();

            $confirmationStats = [
                'total_users' => $totalUsers,
                'read_count' => $confirmations->read_count ?? 0,
                'confirmed_count' => $confirmations->confirmed_count ?? 0,
                'read_percentage' => $totalUsers > 0 ? round(($confirmations->read_count ?? 0) / $totalUsers * 100, 1) : 0,
                'confirmed_percentage' => $totalUsers > 0 ? round(($confirmations->confirmed_count ?? 0) / $totalUsers * 100, 1) : 0,
            ];
        }

        return Inertia::render('announcements/Show', [
            'announcement' => $announcement,
            'confirmationStats' => $confirmationStats,
        ]);
    }

    public function edit(Announcement $announcement): Response
    {
        return Inertia::render('announcements/Edit', [
            'announcement' => $announcement,
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', 'in:urgent,important,normal'],
            'type' => ['required', 'in:general,administrative,maintenance,emergency'],
            'status' => ['required', 'in:draft,published,archived'],
            'is_pinned' => ['required', 'boolean'],
            'requires_confirmation' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['array'],
            'attachments.*.name' => ['required', 'string'],
            'attachments.*.url' => ['required', 'string'],
            'attachments.*.size' => ['nullable', 'integer'],
        ]);

        $validated['updated_by'] = Auth::id();

        // Set published_at if status changed to published and it wasn't set
        if ($validated['status'] === 'published' && ! $announcement->published_at && ! isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $announcement->update($validated);

        return redirect()->route('announcements.index')->with('success', 'Anuncio actualizado exitosamente.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Anuncio eliminado exitosamente.');
    }

    public function confirmations(Announcement $announcement): Response
    {
        $confirmations = $announcement->confirmations()
            ->with('user')
            ->orderByDesc('confirmed_at')
            ->orderByDesc('read_at')
            ->paginate(20);

        return Inertia::render('announcements/Confirmations', [
            'announcement' => $announcement,
            'confirmations' => $confirmations,
        ]);
    }

    public function duplicate(Announcement $announcement): RedirectResponse
    {
        $newAnnouncement = $announcement->replicate([
            'published_at',
            'created_by',
            'updated_by',
        ]);

        $newAnnouncement->title = $announcement->title.' (Copia)';
        $newAnnouncement->status = 'draft';
        $newAnnouncement->published_at = null;
        $newAnnouncement->created_by = Auth::id();
        $newAnnouncement->save();

        return redirect()->route('announcements.edit', $newAnnouncement)
            ->with('success', 'Anuncio duplicado exitosamente.');
    }
}