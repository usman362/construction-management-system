<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MaterialController extends Controller
{
    /**
     * 2026-04-28 — Modal-only flow. See Projects/Clients cleanup notes.
     */
    public function create(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('materials.index', ['new' => 1]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('materials.index', [
            'vendors' => Vendor::all(),
        ]);
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Material::with('vendor');
        $totalRecords = Material::count();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('unit_of_measure', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Columns match DataTable: name, unit, unit_cost, vendor, actions
        $columns = ['name', 'unit_of_measure', 'unit_cost', 'vendor_id'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $orderDir = $request->input('order.0.dir', 'asc');
        $query->orderBy($orderCol, $orderDir);

        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($material) {
                return [
                    'id' => $material->id,
                    'name' => $material->name,
                    'unit_of_measure' => $material->unit_of_measure,
                    'unit_cost' => $material->unit_cost,
                    'vendor_name' => $material->vendor?->name ?? '—',
                    'category' => $material->category,
                    'actions' => $material->id,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit_of_measure' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'vendor_id' => 'nullable|exists:vendors,id',
            'category' => 'nullable|string|max:100',
        ]);

        Material::create($validated);

        return response()->json(['message' => 'Material created successfully']);
    }

    public function show(Request $request, Material $material): JsonResponse|View
    {
        $material->load([
            'vendor',
            'usages' => fn ($q) => $q->with('project')->latest('date')->limit(50),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($material);
        }

        return view('materials.show', ['material' => $material]);
    }

    public function edit(Request $request, Material $material): JsonResponse|View
    {
        $material->load('vendor');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($material);
        }

        return view('materials.edit', ['material' => $material]);
    }

    public function update(Request $request, Material $material): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit_of_measure' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'vendor_id' => 'nullable|exists:vendors,id',
            'category' => 'nullable|string|max:100',
        ]);

        $material->update($validated);

        $message = 'Material updated successfully';

        return $request->ajax() || $request->wantsJson()
            ? response()->json(['message' => $message])
            : redirect()->route('materials.show', $material)->with('success', $message);
    }

    public function destroy(Material $material): JsonResponse
    {
        $material->delete();

        return response()->json(['message' => 'Material deleted successfully']);
    }

    public function recordUsage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'material_id'  => 'required|exists:materials,id',
            'project_id'   => 'required|exists:projects,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'quantity'     => 'required|numeric|min:0',
            'usage_date'   => 'required|date',
            'unit_cost'    => 'nullable|numeric|min:0',
            'description'  => 'nullable|string',
        ]);

        $material  = Material::findOrFail($validated['material_id']);
        $unitCost  = (float) ($validated['unit_cost'] ?? $material->unit_cost);
        $totalCost = (float) $validated['quantity'] * $unitCost;

        $usage = MaterialUsage::create([
            'project_id'   => $validated['project_id'],
            'material_id'  => $validated['material_id'],
            'cost_code_id' => $validated['cost_code_id'] ?? null,
            'date'         => $validated['usage_date'],
            'description'  => $validated['description'] ?? null,
            'quantity'     => $validated['quantity'],
            'unit_cost'    => $unitCost,
            'total_cost'   => $totalCost,
        ]);

        return response()->json([
            'message'    => 'Material usage recorded.',
            'total_cost' => $totalCost,
            'usage_id'   => $usage->id,
        ]);
    }

    /**
     * Mobile-first material quick-log — designed for foremen to one-tap log
     * material consumption from the jobsite. Uses the same `recordUsage`
     * endpoint to persist; this just renders the form.
     */
    public function mobileQuickLog(\App\Models\Project $project): \Illuminate\View\View
    {
        return view('materials.mobile-quick-log', [
            'project'   => $project,
            'materials' => Material::orderBy('name')->get(['id', 'name', 'unit_of_measure', 'unit_cost', 'category']),
            'costCodes' => \App\Models\CostCode::active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }
}
