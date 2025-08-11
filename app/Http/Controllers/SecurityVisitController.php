<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SecurityVisitController extends Controller
{
    public function scanner(): Response
    {
        return Inertia::render('Security/VisitScanner');
    }

    public function validateQR(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => ['required', 'string'],
        ]);

        $visit = Visit::with(['apartment', 'creator'])
            ->where('qr_code', $request->qr_code)
            ->first();

        if (! $visit) {
            return response()->json([
                'valid' => false,
                'message' => 'Código QR no válido',
            ]);
        }

        if (! $visit->canBeUsed()) {
            $message = match ($visit->status) {
                'used' => 'Esta visita ya fue utilizada',
                'expired' => 'Esta visita ha expirado',
                'cancelled' => 'Esta visita fue cancelada',
                default => $visit->isValid() ? 'Esta visita no está disponible' : 'Esta visita ha expirado'
            };

            return response()->json([
                'valid' => false,
                'message' => $message,
                'visit' => $visit,
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Visita válida',
            'visit' => $visit,
        ]);
    }

    public function authorizeEntry(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => ['required', 'string'],
            'security_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $visit = Visit::where('qr_code', $request->qr_code)->first();

        if (! $visit || ! $visit->canBeUsed()) {
            return response()->json([
                'success' => false,
                'message' => 'Código QR no válido o visita no disponible',
            ], 400);
        }

        $visit->markAsUsed(Auth::id());

        if ($request->security_notes) {
            $visit->update(['security_notes' => $request->security_notes]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Entrada autorizada exitosamente',
            'visit' => $visit->load(['apartment', 'creator']),
        ]);
    }

    public function recentEntries(): Response
    {
        $entries = Visit::with(['apartment', 'creator', 'authorizer'])
            ->where('status', 'used')
            ->orderBy('entry_time', 'desc')
            ->take(20)
            ->get();

        return Inertia::render('Security/RecentEntries', [
            'entries' => $entries,
        ]);
    }
}
