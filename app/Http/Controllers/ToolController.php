<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Tool;
use App\Models\ToolAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Tool tracker — catalog + check-out / check-in for hand tools, ladders,
 * small power tools. Heavy/billable equipment lives in Equipment; this
 * keeps the Tools list lean for the daily yard-checkout grind.
 */
class ToolController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tool::query()->with(['currentAssignment.employee:id,first_name,last_name', 'currentAssignment.project:id,project_number,name']);

        if ($status = $request->input('status'))      $query->where('status', $status);
        if ($cat = $request->input('category'))       $query->where('category', $cat);
        if ($loc = $request->input('location'))       $query->where('location', $loc);
        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('asset_tag', 'like', "%{$q}%")
                  ->orWhere('serial_number', 'like', "%{$q}%")
                  ->orWhere('location', 'like', "%{$q}%");
            });
        }

        $tools = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('tools.index', [
            'tools'    => $tools,
            'filters'  => $request->only(['status', 'category', 'location', 'q']),
            'employees'=> Employee::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_number']),
            'projects' => Project::whereIn('status', ['active', 'awarded', 'bidding'])->orderBy('project_number')->get(['id', 'project_number', 'name']),
            'categories' => Tool::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'locations'  => Tool::query()->whereNotNull('location')->distinct()->orderBy('location')->pluck('location'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'asset_tag'        => 'nullable|string|max:50|unique:tools,asset_tag',
            'category'         => 'nullable|string|max:50',
            'location'         => 'nullable|string|max:150',
            'serial_number'    => 'nullable|string|max:100',
            'replacement_cost' => 'nullable|numeric|min:0',
            'purchase_date'    => 'nullable|date',
            'purchase_ticket'  => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,heic,heif,webp',
            'notes'            => 'nullable|string',
        ]);
        $file = $data['purchase_ticket'] ?? null;
        unset($data['purchase_ticket']);
        $tool = Tool::create($data);
        if ($file) $this->attachPurchaseTicket($tool, $file);
        return response()->json(['success' => true, 'message' => 'Tool added.', 'tool' => $tool->fresh()], 201);
    }

    public function update(Request $request, Tool $tool): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'asset_tag'        => 'nullable|string|max:50|unique:tools,asset_tag,' . $tool->id,
            'category'         => 'nullable|string|max:50',
            'location'         => 'nullable|string|max:150',
            'serial_number'    => 'nullable|string|max:100',
            'replacement_cost' => 'nullable|numeric|min:0',
            'purchase_date'    => 'nullable|date',
            'purchase_ticket'  => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,heic,heif,webp',
            'status'           => 'nullable|in:available,issued,lost,retired',
            'notes'            => 'nullable|string',
        ]);
        $file = $data['purchase_ticket'] ?? null;
        unset($data['purchase_ticket']);
        $tool->update($data);
        if ($file) $this->attachPurchaseTicket($tool, $file);
        return response()->json(['success' => true, 'message' => 'Tool updated.', 'tool' => $tool->fresh()]);
    }

    public function destroy(Tool $tool): JsonResponse
    {
        // Clean up the purchase-ticket file too (Storage::delete is a no-op
        // if the path doesn't exist, so this is safe even if the column was
        // already null).
        if ($tool->purchase_ticket_path) {
            Storage::delete($tool->purchase_ticket_path);
        }
        $tool->delete();
        return response()->json(['success' => true, 'message' => 'Tool removed.']);
    }

    /**
     * 2026-05-12 (Brenda): download the attached purchase ticket / receipt.
     * Inline content-disposition so PDFs preview in the browser tab.
     */
    public function downloadPurchaseTicket(Tool $tool)
    {
        if (! $tool->purchase_ticket_path || ! Storage::exists($tool->purchase_ticket_path)) {
            abort(404, 'No purchase ticket on file for this tool.');
        }
        return Storage::download(
            $tool->purchase_ticket_path,
            $tool->purchase_ticket_name ?: ('tool-' . $tool->id . '-ticket'),
            ['Content-Disposition' => 'inline; filename="' . ($tool->purchase_ticket_name ?: 'ticket') . '"']
        );
    }

    /**
     * Remove the attached purchase ticket but keep the tool row.
     */
    public function removePurchaseTicket(Tool $tool): JsonResponse
    {
        if ($tool->purchase_ticket_path) {
            Storage::delete($tool->purchase_ticket_path);
        }
        $tool->update(['purchase_ticket_path' => null, 'purchase_ticket_name' => null]);
        return response()->json(['success' => true, 'message' => 'Purchase ticket removed.']);
    }

    /**
     * Save the uploaded purchase ticket to a tool, replacing any prior file.
     */
    private function attachPurchaseTicket(Tool $tool, $file): void
    {
        // If there was an older ticket, clean it up so storage doesn't bloat.
        if ($tool->purchase_ticket_path) {
            Storage::delete($tool->purchase_ticket_path);
        }
        $path = $file->store('tool-tickets');
        $tool->update([
            'purchase_ticket_path' => $path,
            'purchase_ticket_name' => $file->getClientOriginalName(),
        ]);
    }

    /** Issue a tool to an employee (and optionally a project). */
    public function issue(Request $request, Tool $tool): JsonResponse
    {
        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'project_id'    => 'nullable|exists:projects,id',
            'due_back_date' => 'nullable|date|after_or_equal:today',
            'notes'         => 'nullable|string|max:500',
        ]);

        if ($tool->currentAssignment) {
            return response()->json(['success' => false, 'message' => 'Tool is already issued. Return it first.'], 422);
        }
        if ($tool->status === 'lost' || $tool->status === 'retired') {
            return response()->json(['success' => false, 'message' => "Tool is {$tool->status} — cannot issue."], 422);
        }

        $assignment = ToolAssignment::create($data + [
            'tool_id'     => $tool->id,
            'issued_date' => now()->toDateString(),
            'issued_by'   => auth()->id(),
        ]);
        $tool->update(['status' => 'issued']);

        return response()->json([
            'success'    => true,
            'message'    => 'Issued.',
            'assignment' => $assignment->load(['employee:id,first_name,last_name', 'project:id,project_number']),
        ]);
    }

    /** Mark a tool returned. */
    public function return_(Tool $tool): JsonResponse
    {
        if (!$tool->currentAssignment) {
            return response()->json(['success' => false, 'message' => 'Tool is not currently issued.'], 422);
        }
        $tool->currentAssignment->update(['returned_date' => now()->toDateString()]);
        $tool->update(['status' => 'available']);
        return response()->json(['success' => true, 'message' => 'Tool returned.']);
    }
}
