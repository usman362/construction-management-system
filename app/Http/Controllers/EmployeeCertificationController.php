<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeCertificationController extends Controller
{
    public function store(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'certification_number' => 'nullable|string|max:100',
            'issuing_authority' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:10240', // 10MB
        ]);

        $fileData = [];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $folder = 'certifications/' . $employee->id;
            $path = $file->store($folder, 'documents');
            $fileData = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ];
        }

        $cert = $employee->certifications()->create(
            array_merge($validated, $fileData, ['uploaded_by' => auth()->id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Certification added.',
            'certification' => $cert,
        ], 201);
    }

    public function update(Request $request, Employee $employee, EmployeeCertification $certification): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'certification_number' => 'nullable|string|max:100',
            'issuing_authority' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:10240',
        ]);

        if ($request->hasFile('file')) {
            // Delete old file
            if ($certification->file_path) {
                Storage::disk('documents')->delete($certification->file_path);
            }
            $file = $request->file('file');
            $folder = 'certifications/' . $employee->id;
            $path = $file->store($folder, 'documents');
            $validated['file_path'] = $path;
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_type'] = $file->getClientMimeType();
            $validated['file_size'] = $file->getSize();
        }

        $certification->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Certification updated.',
            'certification' => $certification->fresh(),
        ]);
    }

    public function destroy(Employee $employee, EmployeeCertification $certification): JsonResponse
    {
        if ($certification->file_path) {
            Storage::disk('documents')->delete($certification->file_path);
        }
        $certification->delete();

        return response()->json(['success' => true, 'message' => 'Certification deleted.']);
    }

    public function download(EmployeeCertification $certification)
    {
        if (!$certification->file_path || !Storage::disk('documents')->exists($certification->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('documents')->download($certification->file_path, $certification->file_name);
    }
}
