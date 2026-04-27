<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    /**
     * 2026-04-28 — Modal-only flow (matches Projects cleanup). The dedicated
     * /clients/create page was retired to eliminate the field-shape drift
     * that caused silent save failures. This redirect keeps old bookmarks
     * working by sending the user to the index with `?new=1`, which auto-
     * opens the Add Client modal.
     */
    public function create(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('clients.index', ['new' => 1]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('clients.index');
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Client::withCount('projects');
        $totalRecords = Client::count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vendor_code', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Order (must match DataTable column order: name, contact, email, phone, projects, actions)
        $columns = ['name', 'contact_name', 'email', 'phone', 'projects_count'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $orderDir = $request->input('order.0.dir', 'asc');
        if ($orderCol === 'projects_count') {
            $query->orderBy('projects_count', $orderDir);
        } else {
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
            'data' => $data->map(function ($client) {
                return [
                    'id' => $client->id,
                    'vendor_code' => $client->vendor_code,
                    'name' => $client->name,
                    'contact_name' => $client->contact_name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'projects_count' => $client->projects_count,
                    'actions' => $client->id,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:20',
        ]);

        Client::create($validated);

        return response()->json(['message' => 'Client created successfully']);
    }

    public function show(Request $request, Client $client): JsonResponse|View
    {
        $client->load('projects');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($client);
        }

        return view('clients.show', ['client' => $client]);
    }

    public function edit(Request $request, Client $client): JsonResponse|View
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($client);
        }

        return view('clients.edit', ['client' => $client]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'vendor_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:20',
        ]);

        $client->update($validated);

        return response()->json(['message' => 'Client updated successfully']);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(['message' => 'Client deleted successfully']);
    }
}
