<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Store a document against any documentable model.
     * Expects: documentable_type (e.g. "App\Models\Project"), documentable_id, category, title, file
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'category' => 'required|string|in:proposal,photo,change_order,purchase_order,delivery_ticket,estimate,daily_log,report,correspondence,contract,permit,insurance,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|max:51200', // 50MB max
        ]);

        $file = $request->file('file');

        // Organize: type/id/category/filename
        $typeShort = class_basename($validated['documentable_type']);
        $folder = strtolower($typeShort) . '/' . $validated['documentable_id'] . '/' . $validated['category'];
        $path = $file->store($folder, 'documents');

        $document = Document::create([
            'documentable_type' => $validated['documentable_type'],
            'documentable_id' => $validated['documentable_id'],
            'category' => $validated['category'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'document' => $document->load('uploader'),
        ], 201);
    }

    public function download(Document $document)
    {
        if (!Storage::disk('documents')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('documents')->download($document->file_path, $document->file_name);
    }

    public function destroy(Document $document): JsonResponse
    {
        Storage::disk('documents')->delete($document->file_path);
        $document->delete(); // soft delete

        return response()->json(['success' => true, 'message' => 'Document deleted.']);
    }
}
