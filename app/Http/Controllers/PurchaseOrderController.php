<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Commitment;
use App\Models\Project;
use App\Models\Vendor;
use App\Models\Material;
use App\Models\CostCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders or return DataTable JSON
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }

        return view('purchase-orders.index', [
            'vendors' => Vendor::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'project_number']),
            'costCodes' => CostCode::query()->orderBy('code')->get(['id', 'code', 'name']),
            'costTypes' => \App\Models\CostType::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
            'parentPOs' => PurchaseOrder::whereNull('parent_po_id')->orderBy('po_number')->get(['id', 'po_number']),
        ]);
    }

    /**
     * Return DataTable JSON response with purchase orders
     * Columns: po_number, date, vendor_name, project_name, total_amount, status, actions
     */
    private function dataTable(Request $request): JsonResponse
    {
        $query = PurchaseOrder::query()->with(['vendor', 'project']);

        // Search across po_number, vendor name, project name
        if ($search = $request->input('search.value')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function (Builder $vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('project', function (Builder $pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $totalRecords = PurchaseOrder::count();
        $filteredRecords = (clone $query)->count();

        // Handle sorting
        $orderColIndex = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'asc' ? 'asc' : 'desc';

        switch ($orderColIndex) {
            case 0: // po_number
                $query->orderBy('po_number', $orderDir);
                break;
            case 1: // date
                $query->orderBy('date', $orderDir);
                break;
            case 2: // vendor
                $query->orderBy(
                    Vendor::select('name')
                        ->whereColumn('vendors.id', 'purchase_orders.vendor_id')
                        ->limit(1),
                    $orderDir
                );
                break;
            case 3: // project
                $query->orderBy(
                    Project::select('name')
                        ->whereColumn('projects.id', 'purchase_orders.project_id')
                        ->limit(1),
                    $orderDir
                );
                break;
            case 4: // total_amount
                $query->orderBy('total_amount', $orderDir);
                break;
            case 5: // status
                $query->orderBy('status', $orderDir);
                break;
            default:
                $query->orderBy('po_number', 'desc');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function (PurchaseOrder $po) {
                return [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'date' => $po->date?->format('M j, Y') ?? '—',
                    'vendor' => $po->vendor?->name ?? '—',
                    'project' => $po->project?->name ?? '—',
                    'total_amount' => $po->total_amount,
                    'status' => $po->status,
                    'actions' => $po->id,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created purchase order with items
     * Validates: project_id, vendor_id, cost_code_id (nullable), description, date,
     *            delivery_date (nullable), notes (nullable), items (array)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'vendor_id' => 'required|exists:vendors,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'parent_po_id' => 'nullable|exists:purchase_orders,id',
            'change_order_id' => 'nullable|exists:change_orders,id',
            'po_number' => 'nullable|string|max:50|unique:purchase_orders,po_number',
            'description' => 'required|string|max:500',
            'date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'shipping_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_of_measure' => 'nullable|string|max:50',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.material_id' => 'nullable|exists:materials,id',
        ]);

        foreach ($validated['items'] as &$item) {
            $item['unit_of_measure'] = $item['unit_of_measure'] ?? 'EA';
        }
        unset($item);

        try {
            // Use custom PO number if provided, otherwise auto-generate
            if (!empty($validated['po_number'])) {
                $poNumber = $validated['po_number'];
            } else {
                $lastPo = PurchaseOrder::orderBy('id', 'desc')->first();
                $nextNumber = ($lastPo ? (int)substr($lastPo->po_number, 3) + 1 : 1);
                $poNumber = 'PO-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += (float)$item['quantity'] * (float)$item['unit_cost'];
            }

            $taxRate = (float)($validated['tax_rate'] ?? 0) / 100;
            $taxAmount = bcmul((string)$subtotal, (string)$taxRate, 2);
            $shippingAmount = (float)($validated['shipping_amount'] ?? 0);
            $totalAmount = bcadd(bcadd((string)$subtotal, (string)$taxAmount, 2), (string)$shippingAmount, 2);

            // Create purchase order within transaction
            $po = \DB::transaction(function () use (
                $validated,
                $poNumber,
                $subtotal,
                $taxRate,
                $taxAmount,
                $shippingAmount,
                $totalAmount
            ) {
                $po = PurchaseOrder::create([
                    'project_id' => $validated['project_id'],
                    'parent_po_id' => $validated['parent_po_id'] ?? null,
                    'change_order_id' => $validated['change_order_id'] ?? null,
                    'vendor_id' => $validated['vendor_id'],
                    'cost_code_id' => $validated['cost_code_id'] ?? null,
                    'cost_type_id' => $validated['cost_type_id'] ?? null,
                    'po_number' => $poNumber,
                    'description' => $validated['description'],
                    'date' => $validated['date'],
                    'delivery_date' => $validated['delivery_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'shipping_amount' => $shippingAmount,
                    'total_amount' => $totalAmount,
                    'status' => 'draft',
                ]);

                // Create items
                foreach ($validated['items'] as $item) {
                    $itemTotal = bcmul((string)$item['quantity'], (string)$item['unit_cost'], 2);
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'material_id' => $item['material_id'] ?? null,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_of_measure' => $item['unit_of_measure'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $itemTotal,
                        'received_quantity' => 0,
                        'status' => 'pending',
                    ]);
                }

                // Auto-create commitment so PO shows in committed costs
                $lastCommitment = Commitment::where('project_id', $validated['project_id'])
                    ->orderBy('id', 'desc')
                    ->first();
                $nextCommNum = ($lastCommitment ? (int)substr($lastCommitment->commitment_number, 5) + 1 : 1);
                $commNumber = 'COMM-' . str_pad($nextCommNum, 5, '0', STR_PAD_LEFT);

                $commitData = [
                    'project_id' => $validated['project_id'],
                    'vendor_id' => $validated['vendor_id'],
                    'commitment_number' => $commNumber,
                    'po_number' => $poNumber,
                    'description' => $validated['description'],
                    'amount' => $totalAmount,
                    'committed_date' => $validated['date'],
                    'status' => 'pending',
                ];
                if (!empty($validated['cost_code_id'])) {
                    $commitData['cost_code_id'] = $validated['cost_code_id'];
                }
                Commitment::create($commitData);

                return $po;
            });

            return response()->json([
                'message' => 'Purchase order created successfully',
                'po_id' => $po->id,
                'po_number' => $po->po_number,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating purchase order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified purchase order for modal viewing
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['project', 'vendor', 'costCode', 'items.material']);

        return response()->json([
            'id' => $purchaseOrder->id,
            'po_number' => $purchaseOrder->po_number,
            'date' => $purchaseOrder->date?->format('Y-m-d'),
            'delivery_date' => $purchaseOrder->delivery_date?->format('Y-m-d'),
            'project_id' => $purchaseOrder->project_id,
            'project' => $purchaseOrder->project?->name,
            'vendor_id' => $purchaseOrder->vendor_id,
            'vendor' => $purchaseOrder->vendor?->name,
            'cost_code_id' => $purchaseOrder->cost_code_id,
            'cost_code' => $purchaseOrder->costCode?->code,
            'description' => $purchaseOrder->description,
            'notes' => $purchaseOrder->notes,
            'subtotal' => $purchaseOrder->subtotal,
            'tax_rate' => $purchaseOrder->tax_rate * 100,
            'tax_amount' => $purchaseOrder->tax_amount,
            'shipping_amount' => $purchaseOrder->shipping_amount,
            'total_amount' => $purchaseOrder->total_amount,
            'status' => $purchaseOrder->status,
            'issued_by' => $purchaseOrder->issuedBy?->name,
            'issued_at' => $purchaseOrder->issued_at?->format('M j, Y H:i'),
            'items' => $purchaseOrder->items->map(function (PurchaseOrderItem $item) {
                return [
                    'id' => $item->id,
                    'material_id' => $item->material_id,
                    'material_name' => $item->material?->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_of_measure' => $item->unit_of_measure,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                    'received_quantity' => $item->received_quantity,
                    'status' => $item->status,
                ];
            }),
        ]);
    }

    /**
     * Show the form for editing the specified purchase order
     * Returns PO data with items and lists for dropdowns
     */
    public function edit(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load('items.material');

        return response()->json([
            'id' => $purchaseOrder->id,
            'po_number' => $purchaseOrder->po_number,
            'date' => $purchaseOrder->date?->format('Y-m-d'),
            'delivery_date' => $purchaseOrder->delivery_date?->format('Y-m-d'),
            'project_id' => $purchaseOrder->project_id,
            'vendor_id' => $purchaseOrder->vendor_id,
            'cost_code_id' => $purchaseOrder->cost_code_id,
            'description' => $purchaseOrder->description,
            'notes' => $purchaseOrder->notes,
            'tax_rate' => $purchaseOrder->tax_rate * 100,
            'shipping_amount' => $purchaseOrder->shipping_amount,
            'status' => $purchaseOrder->status,
            'items' => $purchaseOrder->items->map(function (PurchaseOrderItem $item) {
                return [
                    'id' => $item->id,
                    'material_id' => $item->material_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_of_measure' => $item->unit_of_measure,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                    'received_quantity' => $item->received_quantity,
                    'status' => $item->status,
                ];
            }),
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'project_number']),
            'costCodes' => CostCode::orderBy('code')->get(['id', 'code']),
            'materials' => Material::orderBy('name')->get(['id', 'name', 'unit']),
        ]);
    }

    /**
     * Update the specified purchase order and sync items
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'vendor_id' => 'required|exists:vendors,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'parent_po_id' => 'nullable|exists:purchase_orders,id',
            'change_order_id' => 'nullable|exists:change_orders,id',
            'description' => 'required|string|max:500',
            'date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'shipping_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_of_measure' => 'nullable|string|max:50',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.material_id' => 'nullable|exists:materials,id',
        ]);

        foreach ($validated['items'] as &$item) {
            $item['unit_of_measure'] = $item['unit_of_measure'] ?? 'EA';
        }
        unset($item);

        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += (float)$item['quantity'] * (float)$item['unit_cost'];
            }

            $taxRate = (float)($validated['tax_rate'] ?? 0) / 100;
            $taxAmount = bcmul((string)$subtotal, (string)$taxRate, 2);
            $shippingAmount = (float)($validated['shipping_amount'] ?? 0);
            $totalAmount = bcadd(bcadd((string)$subtotal, (string)$taxAmount, 2), (string)$shippingAmount, 2);

            // Update within transaction
            \DB::transaction(function () use (
                $purchaseOrder,
                $validated,
                $subtotal,
                $taxRate,
                $taxAmount,
                $shippingAmount,
                $totalAmount
            ) {
                // Update PO
                $purchaseOrder->update([
                    'project_id' => $validated['project_id'],
                    'parent_po_id' => $validated['parent_po_id'] ?? null,
                    'change_order_id' => $validated['change_order_id'] ?? null,
                    'vendor_id' => $validated['vendor_id'],
                    'cost_code_id' => $validated['cost_code_id'] ?? null,
                    'cost_type_id' => $validated['cost_type_id'] ?? null,
                    'description' => $validated['description'],
                    'date' => $validated['date'],
                    'delivery_date' => $validated['delivery_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'shipping_amount' => $shippingAmount,
                    'total_amount' => $totalAmount,
                ]);

                // Sync items - delete all and recreate
                PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->delete();

                foreach ($validated['items'] as $item) {
                    $itemTotal = bcmul((string)$item['quantity'], (string)$item['unit_cost'], 2);
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'material_id' => $item['material_id'] ?? null,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_of_measure' => $item['unit_of_measure'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $itemTotal,
                        'received_quantity' => 0,
                        'status' => 'pending',
                    ]);
                }
            });

            // Sync linked commitment amount
            $linkedCommitment = Commitment::where('po_number', $purchaseOrder->po_number)->first();
            if ($linkedCommitment) {
                $linkedCommitment->update([
                    'amount' => $totalAmount,
                    'vendor_id' => $validated['vendor_id'],
                    'cost_code_id' => $validated['cost_code_id'] ?? null,
                    'description' => $validated['description'],
                ]);
            }

            return response()->json(['message' => 'Purchase order updated successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating purchase order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete the specified purchase order (only if draft status)
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft purchase orders can be deleted',
            ], 422);
        }

        try {
            \DB::transaction(function () use ($purchaseOrder) {
                PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->delete();
                $purchaseOrder->delete();
            });

            return response()->json(['message' => 'Purchase order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting purchase order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Issue the purchase order (change status to issued)
     */
    public function issue(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft purchase orders can be issued',
            ], 422);
        }

        try {
            $purchaseOrder->update([
                'status' => 'issued',
                'issued_by' => auth()->id(),
                'issued_at' => now(),
            ]);

            return response()->json(['message' => 'Purchase order issued successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error issuing purchase order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Receive items and update quantities
     * If all items received, mark PO as received
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0',
        ]);

        try {
            \DB::transaction(function () use ($purchaseOrder, $validated) {
                foreach ($validated['items'] as $itemData) {
                    $item = PurchaseOrderItem::find($itemData['id']);
                    if ($item && $item->purchase_order_id === $purchaseOrder->id) {
                        $receivedQty = (float)$itemData['received_quantity'];
                        $totalQty = (float)$item->quantity;

                        $item->update([
                            'received_quantity' => $receivedQty,
                            'status' => $receivedQty >= $totalQty ? 'received' : 'partial',
                        ]);
                    }
                }

                // Check if all items are fully received
                $allReceived = $purchaseOrder->items()
                    ->where('received_quantity', '<', \DB::raw('quantity'))
                    ->count() === 0;

                if ($allReceived && count($purchaseOrder->items) > 0) {
                    $purchaseOrder->update(['status' => 'received']);
                }
            });

            return response()->json(['message' => 'Items received successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error receiving items',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add a single item to an existing purchase order
     */
    public function addItem(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'quantity' => 'required|numeric|min:0.01',
            'unit_of_measure' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'material_id' => 'nullable|exists:materials,id',
        ]);

        try {
            \DB::transaction(function () use ($purchaseOrder, $validated) {
                // Create new item
                $itemTotal = bcmul((string)$validated['quantity'], (string)$validated['unit_cost'], 2);
                $newItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'material_id' => $validated['material_id'] ?? null,
                    'description' => $validated['description'],
                    'quantity' => $validated['quantity'],
                    'unit_of_measure' => $validated['unit_of_measure'],
                    'unit_cost' => $validated['unit_cost'],
                    'total_cost' => $itemTotal,
                    'received_quantity' => 0,
                    'status' => 'pending',
                ]);

                // Recalculate PO totals
                $subtotal = $purchaseOrder->items()->sum(\DB::raw('quantity * unit_cost'));
                $taxAmount = bcmul((string)$subtotal, (string)$purchaseOrder->tax_rate, 2);
                $totalAmount = bcadd(
                    bcadd((string)$subtotal, (string)$taxAmount, 2),
                    (string)$purchaseOrder->shipping_amount,
                    2
                );

                $purchaseOrder->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                ]);
            });

            return response()->json(['message' => 'Item added successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error adding item',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove an item from the purchase order and recalculate totals
     */
    public function removeItem(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderItem $item): JsonResponse
    {
        if ($item->purchase_order_id !== $purchaseOrder->id) {
            return response()->json([
                'message' => 'Item does not belong to this purchase order',
            ], 422);
        }

        try {
            \DB::transaction(function () use ($purchaseOrder, $item) {
                $item->delete();

                // Recalculate PO totals
                $subtotal = $purchaseOrder->items()->sum(\DB::raw('quantity * unit_cost'));
                $taxAmount = bcmul((string)$subtotal, (string)$purchaseOrder->tax_rate, 2);
                $totalAmount = bcadd(
                    bcadd((string)$subtotal, (string)$taxAmount, 2),
                    (string)$purchaseOrder->shipping_amount,
                    2
                );

                $purchaseOrder->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                ]);
            });

            return response()->json(['message' => 'Item removed successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error removing item',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate and download PO PDF
     * Uses view 'pdf.purchase-order'
     */
    public function downloadPdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['project', 'vendor', 'costCode', 'items.material', 'issuedBy']);

        try {
            $pdf = Pdf::loadView('pdf.purchase-order', [
                'purchaseOrder' => $purchaseOrder,
            ]);

            return $pdf->download("po-{$purchaseOrder->po_number}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating PDF',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create a Commitment record from this PO for the committed cost log
     */
    public function commitToProject(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            // Check if commitment already exists for this PO
            $existingCommitment = Commitment::where('po_number', $purchaseOrder->po_number)->first();
            if ($existingCommitment) {
                return response()->json([
                    'message' => 'A commitment already exists for this purchase order',
                ], 422);
            }

            // Generate commitment number
            $lastCommitment = Commitment::where('project_id', $purchaseOrder->project_id)
                ->orderBy('id', 'desc')
                ->first();
            $nextNumber = ($lastCommitment ? (int)substr($lastCommitment->commitment_number, 5) + 1 : 1);
            $commNumber = 'COMM-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Create commitment
            $commitment = Commitment::create([
                'project_id' => $purchaseOrder->project_id,
                'vendor_id' => $purchaseOrder->vendor_id,
                'cost_code_id' => $purchaseOrder->cost_code_id,
                'commitment_number' => $commNumber,
                'description' => $purchaseOrder->description,
                'amount' => $purchaseOrder->total_amount,
                'committed_date' => now()->toDateString(),
                'status' => 'released',
            ]);

            return response()->json([
                'message' => 'Commitment created successfully',
                'commitment_id' => $commitment->id,
                'commitment_number' => $commitment->commitment_number,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating commitment',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
