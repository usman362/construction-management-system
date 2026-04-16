<?php

namespace App\Http\Controllers;

use App\Models\CostCode;
use App\Models\CostType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CostCodeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        $costTypes = CostType::active()->orderBy('sort_order')->get();
        return view('cost-codes.index', compact('costTypes'));
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = CostCode::with('costType');
        $totalRecords = CostCode::count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('costType', fn($qt) => $qt->where('name', 'like', "%{$search}%"));
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['code', 'name', 'cost_type_id'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'code';
        $orderDir = $request->input('order.0.dir', 'asc');
        $query->orderBy($orderCol, $orderDir);

        // Paginate
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($costCode) {
                return [
                    'id' => $costCode->id,
                    'code' => $costCode->code,
                    'name' => $costCode->name,
                    'cost_type_id' => $costCode->cost_type_id,
                    'cost_type_name' => $costCode->costType?->name,
                    'is_active' => $costCode->is_active,
                    'actions' => $costCode->id,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:cost_codes|string|max:50',
            'name' => 'required|string|max:255',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'is_active' => 'nullable|boolean',
        ]);

        CostCode::create($validated);

        return response()->json(['message' => 'Cost code created successfully']);
    }

    public function show(CostCode $costCode): JsonResponse
    {
        $costCode->load('costType');
        return response()->json($costCode);
    }

    public function edit(CostCode $costCode): JsonResponse
    {
        $costCode->load('costType');
        return response()->json($costCode);
    }

    public function update(Request $request, CostCode $costCode): JsonResponse
    {
        $validated = $request->validate([
            'code' => "required|unique:cost_codes,code,{$costCode->id}|string|max:50",
            'name' => 'required|string|max:255',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'is_active' => 'nullable|boolean',
        ]);

        $costCode->update($validated);

        return response()->json(['message' => 'Cost code updated successfully']);
    }

    public function destroy(CostCode $costCode): JsonResponse
    {
        $costCode->delete();

        return response()->json(['message' => 'Cost code deleted successfully']);
    }
}
