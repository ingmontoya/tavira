<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ResidentAnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = Auth::id();
        $query = Announcement::with(['createdBy'])
            ->forUser($userId);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('unread')) {
            if ($request->boolean('unread')) {
                $query->whereDoesntHave('confirmations', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->whereNotNull('read_at');
                });
            }
        }

        if ($request->filled('unconfirmed')) {
            if ($request->boolean('unconfirmed')) {
                $query->where('requires_confirmation', true)
                    ->whereDoesntHave('confirmations', function ($q) use ($userId) {
                        $q->where('user_id', $userId)->whereNotNull('confirmed_at');
                    });
            }
        }

        $announcements = $query->orderByDesc('is_pinned')
            ->orderBy(\DB::raw("CASE 
                WHEN priority = 'urgent' THEN 1 
                WHEN priority = 'important' THEN 2 
                WHEN priority = 'normal' THEN 3 
                ELSE 4 
            END"))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Add read/confirmation status to each announcement
        $announcements->through(function ($announcement) use ($userId) {
            $announcement->is_read_by_user = $announcement->isReadBy($userId);
            $announcement->is_confirmed_by_user = $announcement->isConfirmedBy($userId);

            return $announcement;
        });

        $stats = [
            'total' => Announcement::active()->count(),
            'unread' => Announcement::active()
                ->whereDoesntHave('confirmations', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->whereNotNull('read_at');
                })
                ->count(),
            'requiring_confirmation' => Announcement::active()
                ->where('requires_confirmation', true)
                ->whereDoesntHave('confirmations', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->whereNotNull('confirmed_at');
                })
                ->count(),
        ];

        return Inertia::render('resident/announcements/Index', [
            'announcements' => $announcements,
            'filters' => $request->only(['search', 'priority', 'type', 'unread', 'unconfirmed']),
            'stats' => $stats,
        ]);
    }

    public function show(Announcement $announcement): Response
    {
        // Check if announcement is active
        if (! $announcement->is_active) {
            abort(404, 'Anuncio no disponible');
        }

        $userId = Auth::id();
        $announcement->load(['createdBy']);

        // Mark as read
        $announcement->markAsReadBy($userId);

        // Check read/confirmation status
        $announcement->is_read_by_user = true;
        $announcement->is_confirmed_by_user = $announcement->isConfirmedBy($userId);

        return Inertia::render('resident/announcements/Show', [
            'announcement' => $announcement,
        ]);
    }

    public function confirm(Announcement $announcement): RedirectResponse
    {
        // Check if announcement is active and requires confirmation
        if (! $announcement->is_active || ! $announcement->requires_confirmation) {
            return back()->withErrors(['message' => 'No se puede confirmar este anuncio']);
        }

        $userId = Auth::id();
        $announcement->markAsConfirmedBy($userId);

        return back()->with('success', 'Anuncio confirmado exitosamente');
    }
}