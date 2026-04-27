<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentAssignment;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * QR-driven equipment check-in / check-out.
 *
 * Flow:
 *   1. Print a QR sticker for each equipment unit. The QR encodes
 *      `https://yourdomain/equipment/scan/{qr_token}`.
 *   2. Field worker scans the sticker with their phone camera (any QR
 *      reader app, or just the iOS/Android camera since 2018).
 *   3. They land on the scan page, which shows the equipment's current
 *      status (assigned to project X / available) and a one-tap button
 *      to check it in or out.
 *
 * Tokens use UUIDs and are looked up via Equipment::where('qr_token', ...)
 * so an attacker can't enumerate by guessing IDs.
 */
class EquipmentQrController extends Controller
{
    /**
     * Public-ish landing page after a QR scan. Behind auth middleware so
     * even though the URL is on a sticker, only logged-in users can act.
     */
    public function scan(string $token): View
    {
        $equipment = Equipment::where('qr_token', $token)
            ->with(['currentAssignment.project:id,project_number,name', 'vendor:id,name'])
            ->firstOrFail();

        $projects = Project::whereIn('status', ['active', 'awarded', 'bidding'])
            ->orderBy('name')
            ->get(['id', 'project_number', 'name']);

        return view('equipment.scan', [
            'equipment'  => $equipment,
            'projects'   => $projects,
            'isCheckedOut' => $equipment->currentAssignment !== null,
        ]);
    }

    /**
     * Check the equipment OUT — i.e. assign it to a project.
     * Idempotent-ish: if already checked out, return current assignment.
     */
    public function checkOut(Request $request, string $token): JsonResponse
    {
        $equipment = Equipment::where('qr_token', $token)->firstOrFail();

        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'notes'      => 'nullable|string|max:500',
        ]);

        if ($equipment->currentAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'This equipment is already checked out to project '
                    . ($equipment->currentAssignment->project->project_number ?? '?')
                    . '. Check it in first.',
            ], 422);
        }

        $assignment = EquipmentAssignment::create([
            'equipment_id'  => $equipment->id,
            'project_id'    => $data['project_id'],
            'assigned_date' => now()->toDateString(),
            'daily_cost'    => $equipment->daily_rate,
        ]);
        $equipment->update(['status' => 'in_use']);

        return response()->json([
            'success'    => true,
            'message'    => 'Checked out.',
            'assignment' => $assignment->load('project:id,project_number,name'),
        ]);
    }

    /**
     * Check the equipment IN — close the open assignment, set status back
     * to available.
     */
    public function checkIn(Request $request, string $token): JsonResponse
    {
        $equipment = Equipment::where('qr_token', $token)->firstOrFail();

        if (!$equipment->currentAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'This equipment is not currently checked out.',
            ], 422);
        }

        $equipment->currentAssignment->update([
            'returned_date' => now()->toDateString(),
        ]);
        $equipment->update(['status' => 'available']);

        return response()->json([
            'success' => true,
            'message' => 'Checked in. Equipment marked available.',
        ]);
    }

    /**
     * Printable QR sticker page — opens in a new tab from the equipment
     * detail/list page. Uses QRCode.js (CDN) to draw the QR client-side
     * so we don't need a PHP QR library.
     */
    public function printSticker(Equipment $equipment): View
    {
        if (empty($equipment->qr_token)) {
            $equipment->update(['qr_token' => (string) \Illuminate\Support\Str::uuid()]);
        }
        return view('equipment.qr-sticker', [
            'equipment' => $equipment,
            'scanUrl'   => route('equipment.scan', $equipment->qr_token),
        ]);
    }
}
