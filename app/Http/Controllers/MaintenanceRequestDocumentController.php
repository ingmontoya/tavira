<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class MaintenanceRequestDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_maintenance_requests')->only(['index', 'show', 'download']);
        $this->middleware('permission:edit_maintenance_requests')->only(['store', 'update', 'destroy']);
    }

    public function index(MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $documents = $maintenanceRequest->documents()
            ->with('uploadedBy')
            ->orderBy('stage')
            ->orderBy('created_at')
            ->get();

        $documentsByStage = $documents->groupBy('stage');

        return response()->json([
            'documents' => $documents,
            'documentsByStage' => $documentsByStage,
            'stageLabels' => MaintenanceRequestDocument::getStageLabels(),
            'documentTypeLabels' => MaintenanceRequestDocument::getDocumentTypeLabels(),
        ]);
    }

    public function store(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $validated = $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'])
                    ->max(10 * 1024), // 10MB max
            ],
            'stage' => 'required|in:initial_request,evaluation,budgeting,approval,execution,completion,evidence,warranty',
            'document_type' => 'required|in:photo,quote,invoice,receipt,report,specification,permit,warranty,other',
            'description' => 'nullable|string|max:500',
            'is_evidence' => 'boolean',
            'is_required_approval' => 'boolean',
        ]);

        $uploadedDocuments = [];

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $filePath = $file->storeAs('maintenance-requests/'.$maintenanceRequest->id, $fileName, 'public');

            $document = MaintenanceRequestDocument::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'uploaded_by_user_id' => Auth::id(),
                'name' => $originalName,
                'file_path' => $filePath,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'stage' => $validated['stage'],
                'document_type' => $validated['document_type'],
                'description' => $validated['description'],
                'is_evidence' => $validated['is_evidence'] ?? false,
                'is_required_approval' => $validated['is_required_approval'] ?? false,
                'metadata' => [
                    'original_name' => $originalName,
                    'uploaded_at' => now()->toISOString(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            $document->load('uploadedBy');
            $uploadedDocuments[] = $document;
        }

        return response()->json([
            'success' => true,
            'message' => 'Documentos subidos exitosamente.',
            'documents' => $uploadedDocuments,
        ]);
    }

    public function show(MaintenanceRequest $maintenanceRequest, MaintenanceRequestDocument $document): JsonResponse
    {
        if ($document->maintenance_request_id !== $maintenanceRequest->id) {
            return response()->json(['error' => 'Documento no encontrado.'], 404);
        }

        $document->load('uploadedBy');

        return response()->json($document);
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest, MaintenanceRequestDocument $document): JsonResponse
    {
        if ($document->maintenance_request_id !== $maintenanceRequest->id) {
            return response()->json(['error' => 'Documento no encontrado.'], 404);
        }

        $validated = $request->validate([
            'stage' => 'required|in:initial_request,evaluation,budgeting,approval,execution,completion,evidence,warranty',
            'document_type' => 'required|in:photo,quote,invoice,receipt,report,specification,permit,warranty,other',
            'description' => 'nullable|string|max:500',
            'is_evidence' => 'boolean',
            'is_required_approval' => 'boolean',
        ]);

        $document->update($validated);
        $document->load('uploadedBy');

        return response()->json([
            'success' => true,
            'message' => 'Documento actualizado exitosamente.',
            'document' => $document,
        ]);
    }

    public function download(MaintenanceRequest $maintenanceRequest, MaintenanceRequestDocument $document)
    {
        if ($document->maintenance_request_id !== $maintenanceRequest->id) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('public')->download($document->file_path, $document->name);
    }

    public function destroy(MaintenanceRequest $maintenanceRequest, MaintenanceRequestDocument $document): JsonResponse
    {
        if ($document->maintenance_request_id !== $maintenanceRequest->id) {
            return response()->json(['error' => 'Documento no encontrado.'], 404);
        }

        // Delete the file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Documento eliminado exitosamente.',
        ]);
    }
}
