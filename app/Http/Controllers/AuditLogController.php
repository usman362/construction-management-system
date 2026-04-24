<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Paginated audit history across every Auditable entity.
     * Filterable by entity type, event, user, and date range.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with(['user', 'auditable']);

        if ($type = $request->input('entity_type')) {
            $query->where('auditable_type', $type);
        }

        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        // Distinct type/user values for the filter dropdowns.
        $entityTypes = AuditLog::query()
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type');

        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('audit-logs.index', [
            'logs'        => $logs,
            'entityTypes' => $entityTypes,
            'users'       => $users,
            'filters'     => $request->only(['entity_type', 'event', 'user_id', 'date_from', 'date_to']),
        ]);
    }
}
