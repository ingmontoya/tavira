<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assembly;
use App\Models\ConjuntoConfig;
use App\Models\Correspondence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunicationController extends Controller
{
    /**
     * Get communication statistics for mobile dashboard
     */
    public function stats(Request $request)
    {
        try {
            $user = Auth::user();
            $conjuntoConfig = ConjuntoConfig::first();

            if (! $conjuntoConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conjunto configuration not found',
                ], 404);
            }

            // Get unread announcements count
            $unreadAnnouncements = Announcement::published()
                ->whereDoesntHave('confirmations', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->count();

            // Get unread correspondence count
            $unreadCorrespondence = Correspondence::where('status', 'pending')
                ->whereHas('apartment.residents', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->count();

            // Get active assemblies count
            $activeAssemblies = Assembly::where('status', 'in_progress')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_announcements' => $unreadAnnouncements,
                    'unread_correspondence' => $unreadCorrespondence,
                    'active_assemblies' => $activeAssemblies,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving communication stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent communication activity
     */
    public function recentActivity(Request $request)
    {
        try {
            $user = Auth::user();
            $conjuntoConfig = ConjuntoConfig::first();

            if (! $conjuntoConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conjunto configuration not found',
                ], 404);
            }

            $limit = $request->get('limit', 10);
            $activities = collect();

            // Get recent announcements
            $recentAnnouncements = Announcement::published()
                ->latest('published_at')
                ->limit(5)
                ->get()
                ->map(function ($announcement) {
                    return [
                        'id' => $announcement->id,
                        'type' => 'announcement',
                        'title' => $announcement->title,
                        'description' => \Str::limit($announcement->content, 100),
                        'created_at' => $announcement->published_at,
                        'priority' => $announcement->priority,
                    ];
                });

            // Get recent correspondence
            $recentCorrespondence = Correspondence::whereHas('apartment.residents', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
                ->latest('created_at')
                ->limit(3)
                ->get()
                ->map(function ($correspondence) {
                    return [
                        'id' => $correspondence->id,
                        'type' => 'correspondence',
                        'title' => $correspondence->sender_name.' - '.$correspondence->type,
                        'description' => \Str::limit($correspondence->description, 100),
                        'created_at' => $correspondence->created_at,
                        'status' => $correspondence->status,
                    ];
                });

            // Get recent assemblies
            $recentAssemblies = Assembly::latest('created_at')
                ->limit(3)
                ->get()
                ->map(function ($assembly) {
                    return [
                        'id' => $assembly->id,
                        'type' => 'assembly',
                        'title' => $assembly->title,
                        'description' => \Str::limit($assembly->description ?? '', 100),
                        'created_at' => $assembly->created_at,
                        'status' => $assembly->status,
                        'scheduled_at' => $assembly->scheduled_at,
                    ];
                });

            // Merge and sort all activities
            $activities = $recentAnnouncements
                ->concat($recentCorrespondence)
                ->concat($recentAssemblies)
                ->sortByDesc('created_at')
                ->take($limit)
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $activities,
                    'count' => $activities->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving recent activity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
