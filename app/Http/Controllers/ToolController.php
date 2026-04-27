<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Tool;
use App\Models\ToolAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('asset_tag', 'like', "%{$q}%")
                  ->orWhere('serial_number', 'like', "%{$q}%");
            });
        }

        $tools = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('tools.index', [
            'tools'    => $tools,
            'filters'  => $request->only(['status', 'category', 'q']),
            'employees'=> Employee::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_number']),
            'projects' => Project::whereIn('status', ['active', 'awarded', 'bidding'])->orderBy('project_number')->get(['id', 'project_number', 'name']),
            'categories' => Tool::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'asset_tag'        => 'nullable|string|max:50|unique:tools,asset_tag',
            'category'         => 'nullable|string|max:50',
            'serial_number'    => 'nullable|string|max:100',
            'replacement_cost' => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);
        $tool = Tool::create($data);
        return response()->json(['success' => true, 'message' => 'Tool added.', 'tool' => $tool], 201);
    }

    public function update(Request $request, Tool $tool): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'asset_tag'        => 'nullable|string|max:50|unique:tools,asset_tag,' . $tool->id,
            'category'         => 'nullable|string|max:50',
            'serial_number'    => 'nullable|string|max:100',
            'replacement_cost' => 'nullable|numeric|min:0',
            'status'           => 'nullable|in:available,issued,lost,retired',
            'notes'            => 'nullable|string',
        ]);
        $tool->update($data);
        return response()->json(['success' => true, 'message' => 'Tool updated.', 'tool' => $tool]);
    }

    public function destroy(Tool $tool): JsonResponse
    {
        $tool->delete();
        return response()->json(['success' => true, 'message' => 'Tool removed.']);
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
