<?php

namespace App\Http\Controllers;

use App\Models\CostCode;
use App\Models\Equipment;
use App\Models\FuelLog;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuelLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = FuelLog::query()->with([
            'equipment:id,name,model_number',
            'project:id,project_number,name',
            'costCode:id,code,name',
            'logger:id,name',
        ]);

        if ($eqId = $request->input('equipment_id'))    $query->where('equipment_id', $eqId);
        if ($projId = $request->input('project_id'))    $query->where('project_id', $projId);
        if ($from = $request->input('from'))            $query->whereDate('fuel_date', '>=', $from);
        if ($to = $request->input('to'))                $query->whereDate('fuel_date', '<=', $to);

        $logs = $query->orderByDesc('fuel_date')->orderByDesc('id')->paginate(50)->withQueryString();

        // Summary roll-up across the filtered set.
        $summaryQuery = (clone $query);
        $summary = [
            'total_gallons' => (float) $summaryQuery->sum('gallons'),
            'total_cost'    => (float) (clone $query)->sum('total_cost'),
            'count'         => $summaryQuery->count(),
        ];

        return view('fuel-logs.index', [
            'logs'      => $logs,
            'summary'   => $summary,
            'filters'   => $request->only(['equipment_id', 'project_id', 'from', 'to']),
            'equipment' => Equipment::orderBy('name')->get(['id', 'name', 'model_number']),
            'projects'  => Project::orderBy('project_number')->get(['id', 'project_number', 'name']),
            'costCodes' => CostCode::active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment_id'        => 'required|exists:equipment,id',
            'project_id'          => 'nullable|exists:projects,id',
            'cost_code_id'        => 'nullable|exists:cost_codes,id',
            'fuel_date'           => 'required|date',
            'fuel_type'           => 'nullable|string|max:30',
            'gallons'             => 'required|numeric|min:0.01',
            'price_per_gallon'    => 'required|numeric|min:0',
            'odometer_reading'    => 'nullable|integer|min:0',
            'hour_meter_reading'  => 'nullable|integer|min:0',
            'vendor_name'         => 'nullable|string|max:150',
            'receipt_number'      => 'nullable|string|max:50',
            'notes'               => 'nullable|string',
        ]);

        $log = FuelLog::create($data + ['logged_by' => auth()->id()]);

        return response()->json([
            'success' => true,
            'message' => 'Fuel log saved ($' . number_format((float) $log->total_cost, 2) . ').',
            'log'     => $log,
        ], 201);
    }

    public function destroy(FuelLog $fuelLog): JsonResponse
    {
        $fuelLog->delete();
        return response()->json(['success' => true, 'message' => 'Fuel log removed.']);
    }
}
