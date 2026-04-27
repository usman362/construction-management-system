<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\Document;
use App\Models\Project;
use App\Models\Rfi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Project Photo Gallery — pulls every photo attached to anything inside a
 * project (daily logs, RFIs, change orders, the project itself) into a
 * single chronological gallery.
 *
 * Filtered by date range and source-type so the PM can pull "all foundation
 * photos in March" or "everything attached to RFIs" in one screen.
 *
 * No new tables — everything is materialized from the existing polymorphic
 * `documents` table where category='photo'.
 */
class ProjectPhotoGalleryController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        // Build a cross-source query: documents directly on the project,
        // plus documents on any of the project's daily logs, RFIs, or COs.
        // We do this with whereIn on documentable_id grouped by morph type
        // rather than 4 unions — way fewer queries.
        $dailyLogIds    = DailyLog::where('project_id', $project->id)->pluck('id');
        $rfiIds         = Rfi::where('project_id', $project->id)->pluck('id');
        $changeOrderIds = $project->changeOrders()->pluck('id');

        $query = Document::query()
            ->where('category', 'photo')
            ->where(function ($w) use ($project, $dailyLogIds, $rfiIds, $changeOrderIds) {
                $w->where(function ($x) use ($project) {
                    $x->where('documentable_type', \App\Models\Project::class)
                      ->where('documentable_id', $project->id);
                });
                if ($dailyLogIds->isNotEmpty()) {
                    $w->orWhere(function ($x) use ($dailyLogIds) {
                        $x->where('documentable_type', \App\Models\DailyLog::class)
                          ->whereIn('documentable_id', $dailyLogIds);
                    });
                }
                if ($rfiIds->isNotEmpty()) {
                    $w->orWhere(function ($x) use ($rfiIds) {
                        $x->where('documentable_type', \App\Models\Rfi::class)
                          ->whereIn('documentable_id', $rfiIds);
                    });
                }
                if ($changeOrderIds->isNotEmpty()) {
                    $w->orWhere(function ($x) use ($changeOrderIds) {
                        $x->where('documentable_type', \App\Models\ChangeOrder::class)
                          ->whereIn('documentable_id', $changeOrderIds);
                    });
                }
            })
            ->with('uploader:id,name');

        // Filters
        if ($source = $request->input('source')) {
            $modelMap = [
                'project'      => \App\Models\Project::class,
                'daily_log'    => \App\Models\DailyLog::class,
                'rfi'          => \App\Models\Rfi::class,
                'change_order' => \App\Models\ChangeOrder::class,
            ];
            if (isset($modelMap[$source])) {
                $query->where('documentable_type', $modelMap[$source]);
            }
        }
        if ($from = $request->input('from')) $query->whereDate('created_at', '>=', $from);
        if ($to = $request->input('to'))     $query->whereDate('created_at', '<=', $to);

        $photos = $query->orderByDesc('created_at')->paginate(48)->withQueryString();

        // Annotate each row with a friendly source label since morph type is
        // an FQCN that the view shouldn't know about.
        $photos->getCollection()->transform(function ($doc) {
            $doc->source_label = match ($doc->documentable_type) {
                \App\Models\Project::class      => 'Project',
                \App\Models\DailyLog::class     => 'Daily Log',
                \App\Models\Rfi::class          => 'RFI',
                \App\Models\ChangeOrder::class  => 'Change Order',
                default                          => class_basename($doc->documentable_type),
            };
            return $doc;
        });

        return view('projects.photo-gallery', [
            'project' => $project,
            'photos'  => $photos,
            'filters' => $request->only(['source', 'from', 'to']),
            'totalPhotoCount' => $photos->total(),
        ]);
    }
}
