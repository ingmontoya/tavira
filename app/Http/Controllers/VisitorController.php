<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    /**
     * Look up visitor data by QR code
     */
    public function lookup(string $qrCode): JsonResponse
    {
        // Find the visit by QR code with related apartment data
        $visit = Visit::with(['apartment', 'creator'])
            ->where('qr_code', $qrCode)
            ->first();

        if (! $visit) {
            return response()->json([
                'success' => false,
                'message' => 'Código QR no encontrado',
                'error' => 'QR_NOT_FOUND',
            ], 404);
        }

        // Check if the visit is still valid (within time window)
        if (! $visit->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Este código QR ha expirado',
                'error' => 'QR_EXPIRED',
                'visitor' => $this->formatVisitorData($visit),
            ], 410); // 410 Gone - resource no longer available
        }

        return response()->json([
            'success' => true,
            'visitor' => $this->formatVisitorData($visit),
        ]);
    }

    /**
     * Authorize a visit (allow entry)
     */
    public function authorizeVisit(Request $request, int $visitId): JsonResponse
    {
        $visit = Visit::find($visitId);

        if (! $visit) {
            return response()->json([
                'success' => false,
                'message' => 'Visita no encontrada',
            ], 404);
        }

        if (! $visit->canBeUsed()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta visita no puede ser autorizada (ya fue usada o expiró)',
            ], 422);
        }

        // Get the user who authorized (from auth or request)
        $authorizedBy = auth()->user()?->id ?? $request->input('authorized_by');

        $visit->update([
            'status' => 'active',
            'entry_time' => now(),
            'authorized_by' => $authorizedBy,
            'security_notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visita autorizada exitosamente',
            'visitor' => $this->formatVisitorData($visit->fresh()),
        ]);
    }

    /**
     * Deny a visit
     */
    public function denyVisit(Request $request, int $visitId): JsonResponse
    {
        $visit = Visit::find($visitId);

        if (! $visit) {
            return response()->json([
                'success' => false,
                'message' => 'Visita no encontrada',
            ], 404);
        }

        if ($visit->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Esta visita no puede ser denegada (ya fue procesada)',
            ], 422);
        }

        // Get the user who denied (from auth or request)
        $deniedBy = auth()->user()?->id ?? $request->input('denied_by');

        $visit->update([
            'status' => 'denied',
            'authorized_by' => $deniedBy,
            'security_notes' => $request->input('notes', 'Visita denegada por portería'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visita denegada exitosamente',
            'visitor' => $this->formatVisitorData($visit->fresh()),
        ]);
    }

    /**
     * Mark a visit as completed (visitor has left)
     */
    public function completeVisit(Request $request, int $visitId): JsonResponse
    {
        $visit = Visit::find($visitId);

        if (! $visit) {
            return response()->json([
                'success' => false,
                'message' => 'Visita no encontrada',
            ], 404);
        }

        if ($visit->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Solo las visitas activas pueden ser completadas',
            ], 422);
        }

        $visit->update([
            'status' => 'completed',
            'exit_time' => now(),
            'security_notes' => $visit->security_notes."\n".($request->input('notes') ?? 'Visita completada por portería'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visita marcada como completada',
            'visitor' => $this->formatVisitorData($visit->fresh()),
        ]);
    }

    /**
     * Format visit data for API response
     */
    private function formatVisitorData(Visit $visit): array
    {
        $apartment = $visit->apartment;
        $creator = $visit->creator;

        return [
            'id' => $visit->id,
            'name' => $visit->visitor_name,
            'document' => ($visit->visitor_document_type ?? 'CC').' '.$visit->visitor_document_number,
            'phone' => $visit->visitor_phone,
            'apartment' => $apartment ? ($apartment->tower.'-'.$apartment->number) : 'N/A',
            'resident' => $creator ? $creator->name : 'N/A',
            'visitDate' => $visit->valid_from->toISOString(),
            'validUntil' => $visit->valid_until->toISOString(),
            'notes' => $visit->visit_reason ?? $visit->security_notes ?? 'Sin observaciones',
            'status' => $visit->status,
            'qrCode' => $visit->qr_code,
            'entryTime' => $visit->entry_time?->toISOString(),
            'exitTime' => $visit->exit_time?->toISOString(),
            'authorizedBy' => $visit->authorizer?->name,
            // Extra fields for the mobile app
            'isValid' => $visit->isValid(),
            'canBeUsed' => $visit->canBeUsed(),
            'formattedValidity' => $visit->formatted_validity,
        ];
    }
}
