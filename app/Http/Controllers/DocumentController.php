<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Store a document against any documentable model.
     * Expects: documentable_type (e.g. "App\Models\Project"), documentable_id, category, title, file
     *
     * 2026-05-30 (Brenda): "I am getting an error when I try to upload
     * documents" — hardened against the three silent-failure modes:
     *  1) storage/app/documents dir not yet created → make it.
     *  2) `documents` disk has `throw=false` → wrap in try/catch so a
     *     write failure surfaces as a real 4xx/5xx, not a half-row.
     *  3) PHP upload_max_filesize / post_max_size truncating the request
     *     so $request->file('file') is null → return a friendly error
     *     telling the user the file was too big for the server, not the
     *     misleading "file is required" Laravel default.
     */
    public function store(Request $request): JsonResponse
    {
        // Catch oversize uploads BEFORE validation. When the request body
        // is bigger than php.ini post_max_size, Laravel sees an empty
        // request and would say "file is required" — confusing for users.
        if (empty($_FILES) && empty($request->all())
            && $_SERVER['CONTENT_LENGTH'] ?? 0 > 0) {
            return response()->json([
                'success' => false,
                'message' => 'The file is larger than the server allows. Please use a smaller file (under 50MB).',
            ], 413);
        }

        $validated = $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id'   => 'required|integer',
            'category'          => 'required|string|in:proposal,photo,change_order,purchase_order,delivery_ticket,estimate,daily_log,report,correspondence,contract,permit,insurance,other',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string|max:1000',
            // 2026-07-01 QA: restrict to construction-doc types Brenda actually
            // uploads. Blocks .php/.exe/.js/.html masquerading as documents.
            // Uses `mimetypes` (file's real MIME) not `mimes` (extension) so a
            // renamed .exe → .pdf gets rejected.
            'file'              => [
                'required', 'file', 'max:51200',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                    .'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                    .'application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,'
                    .'image/jpeg,image/png,image/gif,image/webp,image/heic,image/heif,'
                    .'text/plain,text/csv,application/csv,application/zip,application/x-zip-compressed',
            ],
        ]);

        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file was corrupted or not received. Please try again.',
            ], 422);
        }

        // Organize: type/id/category/filename
        $typeShort = class_basename($validated['documentable_type']);
        $folder    = strtolower($typeShort) . '/' . $validated['documentable_id'] . '/' . $validated['category'];

        try {
            // Make sure the disk root exists. Storage::disk()->put() does
            // create intermediate dirs on Linux, but on a fresh checkout
            // the documents root itself may not be there yet.
            $disk = Storage::disk('documents');
            if (! $disk->exists($folder)) {
                $disk->makeDirectory($folder);
            }

            $path = $file->store($folder, 'documents');
            if (! $path) {
                throw new \RuntimeException('Filesystem returned no path. Check disk permissions on storage/app/documents.');
            }
        } catch (\Throwable $e) {
            Log::error('Document upload failed', [
                'documentable_type' => $validated['documentable_type'],
                'documentable_id'   => $validated['documentable_id'],
                'category'          => $validated['category'],
                'error'             => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not save the file to storage. ' . $e->getMessage(),
            ], 500);
        }

        $document = Document::create([
            'documentable_type' => $validated['documentable_type'],
            'documentable_id'   => $validated['documentable_id'],
            'category'          => $validated['category'],
            'title'             => $validated['title'],
            'description'       => $validated['description'] ?? null,
            'file_path'         => $path,
            'file_name'         => $file->getClientOriginalName(),
            'file_type'         => $file->getClientMimeType(),
            'file_size'         => $file->getSize(),
            'uploaded_by'       => auth()->id(),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Document uploaded successfully.',
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
