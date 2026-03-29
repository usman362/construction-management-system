<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function create(): View
    {
        return view('vendors.create');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('vendors.index');
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Vendor::query();
        $totalRecords = Vendor::count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('specialty', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Order (columns match DataTable: name, contact, specialty, phone, preferred, active)
        $columns = ['name', 'contact_name', 'specialty', 'phone', 'is_preferred', 'is_active'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
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
            'data' => $data->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'contact_name' => $vendor->contact_name,
                    'email' => $vendor->email,
                    'type' => $vendor->type,
                    'specialty' => $vendor->specialty,
                    'phone' => $vendor->phone,
                    'is_preferred' => (bool) $vendor->is_preferred,
                    'is_active' => (bool) $vendor->is_active,
                    'actions' => $vendor->id,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:20',
            'type' => 'nullable|in:subcontractor,supplier,rental,other',
            'specialty' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_preferred' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_preferred'] = $request->boolean('is_preferred');

        Vendor::create($validated);

        return response()->json(['message' => 'Vendor created successfully']);
    }

    public function show(Request $request, Vendor $vendor): JsonResponse|View
    {
        $vendor->load(['commitments.project', 'invoices.project']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($vendor);
        }

        return view('vendors.show', ['vendor' => $vendor]);
    }

    public function edit(Request $request, Vendor $vendor): JsonResponse|View
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($vendor);
        }

        return view('vendors.edit', ['vendor' => $vendor]);
    }

    public function update(Request $request, Vendor $vendor): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:20',
            'type' => 'nullable|in:subcontractor,supplier,rental,other',
            'specialty' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_preferred' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_preferred'] = $request->boolean('is_preferred');

        $vendor->update($validated);

        $message = 'Vendor updated successfully';

        return $request->ajax() || $request->wantsJson()
            ? response()->json(['message' => $message])
            : redirect()->route('vendors.show', $vendor)->with('success', $message);
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted successfully']);
    }
}
