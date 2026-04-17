<?php

namespace App\Http\Controllers;

use App\Models\Craft;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CraftController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('crafts.index');
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Craft::withCount('employees');
        $totalRecords = Craft::count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['code','name','base_hourly_rate','overtime_multiplier','billable_rate','employees_count','is_active'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'code';
        $orderDir = $request->input('order.0.dir', 'asc');
        if ($orderCol !== 'employees_count') {
            $query->orderBy($orderCol, $orderDir);
        }

        // Paginate
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($craft) {
                return [
                    'id' => $craft->id,
                    'code' => $craft->code,
                    'name' => $craft->name,
                    'description' => $craft->description,
                    'base_hourly_rate' => $craft->base_hourly_rate,
                    'overtime_multiplier' => $craft->overtime_multiplier,
                    'billable_rate' => $craft->billable_rate,
                    'ot_billable_rate' => $craft->ot_billable_rate,
                    'is_active' => $craft->is_active,
                    'employees_count' => $craft->employees_count,
                    'actions' => $craft->id,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->craftRules());
        Craft::create($validated);
        return response()->json(['message' => 'Craft created successfully']);
    }

    public function show(Craft $craft): JsonResponse
    {
        $craft->load('employees');
        return response()->json($craft);
    }

    public function edit(Craft $craft): JsonResponse
    {
        return response()->json($craft);
    }

    public function update(Request $request, Craft $craft): JsonResponse
    {
        $validated = $request->validate($this->craftRules($craft->id));
        $craft->update($validated);
        return response()->json(['message' => 'Craft updated successfully']);
    }

    public function destroy(Craft $craft): JsonResponse
    {
        $craft->delete();
        return response()->json(['message' => 'Craft deleted successfully']);
    }

    private function craftRules(?int $ignoreId = null): array
    {
        $uniqueRule = $ignoreId ? "unique:crafts,code,{$ignoreId}" : 'unique:crafts';
        return [
            'code' => "required|{$uniqueRule}|string|max:50",
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_hourly_rate' => 'required|numeric|min:0',
            'overtime_multiplier' => 'required|numeric|min:0',
            'billable_rate' => 'required|numeric|min:0',
            'ot_billable_rate' => 'nullable|numeric|min:0',
            'wc_st_rate' => 'nullable|numeric|min:0',
            'wc_ot_rate' => 'nullable|numeric|min:0',
            'fica_st_rate' => 'nullable|numeric|min:0',
            'fica_ot_rate' => 'nullable|numeric|min:0',
            'suta_st_rate' => 'nullable|numeric|min:0',
            'suta_ot_rate' => 'nullable|numeric|min:0',
            'benefits_st_rate' => 'nullable|numeric|min:0',
            'benefits_ot_rate' => 'nullable|numeric|min:0',
            'overhead_rate' => 'nullable|numeric|min:0',
        ];
    }
}
