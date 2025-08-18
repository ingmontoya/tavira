<?php

namespace App\Http\Controllers;

use App\Models\CorrespondenceAttachment;
use App\Models\MaintenanceRequestDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        // Gather all documents from different modules
        $documents = collect();

        // Add maintenance request documents
        $maintenanceDocuments = MaintenanceRequestDocument::with(['uploadedBy', 'maintenanceRequest'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'filename' => $doc->name,
                    'created_at' => $doc->created_at,
                    'module' => 'Mantenimiento',
                    'uploader' => $doc->uploadedBy->name ?? 'Usuario desconocido',
                    'type' => 'maintenance_document',
                    'file_size' => $doc->formatted_file_size,
                    'file_type' => $doc->file_type,
                ];
            });

        $documents = $documents->merge($maintenanceDocuments);

        // Add correspondence attachments
        $correspondenceDocuments = CorrespondenceAttachment::with(['uploadedBy', 'correspondence'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'filename' => $doc->original_filename,
                    'created_at' => $doc->created_at,
                    'module' => 'Correspondencia',
                    'uploader' => $doc->uploadedBy->name ?? 'Usuario desconocido',
                    'type' => 'correspondence_attachment',
                    'file_size' => $doc->file_size_human,
                    'file_type' => $doc->mime_type,
                ];
            });

        $documents = $documents->merge($correspondenceDocuments);

        // Sort all documents by creation date (newest first)
        $documents = $documents->sortByDesc('created_at')->values();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
        ]);
    }
}
