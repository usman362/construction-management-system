<?php

namespace App\Console\Commands;

use App\Models\ChangeOrder;
use App\Models\DailyLog;
use App\Models\Document;
use App\Models\EquipmentAssignment;
use App\Models\Project;
use App\Models\Rfi;
use App\Models\TimeClockEntry;
use App\Models\Timesheet;
use App\Models\User;
use App\Notifications\ProjectDailySummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * 2026-05-12 (Brenda — Phase 4 recommendation).
 *
 * Run weekdays at 5pm. Builds a per-project rollup for TODAY and
 * sends a single digest email to every active PM + Admin user. Each
 * row covers: labor hours/cost booked today, crew on site, daily-log
 * status, photo upload count, equipment used, plus a quick tail of
 * open RFIs and pending COs.
 *
 * One email per recipient (not per project) — keeps the inbox sane
 * even with 20+ active jobs.
 */
class SendProjectDailySummary extends Command
{
    protected $signature = 'projects:daily-summary
                            {--dry-run : Preview without sending}
                            {--date= : Override the target date (YYYY-MM-DD), default = today}';

    protected $description = 'Email a per-PM digest of every active project\'s status for today.';

    public function handle(): int
    {
        $today    = $this->option('date') ? \Carbon\Carbon::parse($this->option('date'))->startOfDay() : now()->startOfDay();
        $dayStart = $today->copy();
        $dayEnd   = $today->copy()->endOfDay();

        // Active projects only — closed / completed are out of scope for a
        // "today" snapshot.
        $projects = Project::query()
            ->whereNotIn('status', ['closed', 'completed', 'cancelled'])
            ->orderBy('project_number')
            ->get(['id', 'project_number', 'name', 'status']);

        $projectIds = $projects->pluck('id');

        // Today's labor totals per project
        $labor = Timesheet::query()
            ->whereDate('date', $today->toDateString())
            ->whereIn('status', ['approved', 'submitted', 'draft'])
            ->whereIn('project_id', $projectIds)
            ->selectRaw('project_id, SUM(total_hours) as hours, SUM(total_cost) as cost')
            ->groupBy('project_id')
            ->get()->keyBy('project_id');

        // Today's crew per project — pull employee names that booked any
        // hours today, OR are currently clocked in on the project.
        $crewMap = Timesheet::query()
            ->whereDate('date', $today->toDateString())
            ->whereIn('project_id', $projectIds)
            ->with('employee:id,first_name,last_name')
            ->get(['employee_id', 'project_id'])
            ->groupBy('project_id')
            ->map(function ($rows) {
                return $rows->pluck('employee')->filter()->unique('id')
                    ->map(fn ($e) => trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? '')))
                    ->filter()->take(8)->implode(', ');
            });

        // Open clock entries (still on site at 5pm)
        $openPunches = TimeClockEntry::query()
            ->where('status', 'open')
            ->whereIn('project_id', $projectIds)
            ->selectRaw('project_id, COUNT(*) as c')->groupBy('project_id')
            ->get()->keyBy('project_id');

        // Daily log filed today?
        $dailyLogs = DailyLog::query()
            ->whereIn('project_id', $projectIds)
            ->whereDate('date', $today->toDateString())
            ->select('project_id')->get()->keyBy('project_id');

        // Photos uploaded today (Documents morph'd to Project, category=photo)
        $photos = Document::query()
            ->where('documentable_type', \App\Models\Project::class)
            ->where('category', 'photo')
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereIn('documentable_id', $projectIds)
            ->selectRaw('documentable_id as project_id, COUNT(*) as c')->groupBy('documentable_id')
            ->get()->keyBy('project_id');

        // Equipment on-site (still assigned, not yet returned)
        $equipment = EquipmentAssignment::query()
            ->whereNull('returned_date')
            ->whereIn('project_id', $projectIds)
            ->selectRaw('project_id, COUNT(*) as c')->groupBy('project_id')
            ->get()->keyBy('project_id');

        // Open RFIs / Pending COs (carry-over count, not just today)
        $openRfis = Rfi::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('status', ['submitted', 'in_review'])
            ->selectRaw('project_id, COUNT(*) as c')->groupBy('project_id')
            ->get()->keyBy('project_id');

        $pendingCos = ChangeOrder::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'pending')
            ->selectRaw('project_id, COUNT(*) as c')->groupBy('project_id')
            ->get()->keyBy('project_id');

        $stats = $projects->map(function ($p) use ($labor, $crewMap, $openPunches, $dailyLogs, $photos, $equipment, $openRfis, $pendingCos) {
            return (object) [
                'project'        => $p,
                'hours'          => (float) ($labor->get($p->id)->hours ?? 0),
                'cost'           => (float) ($labor->get($p->id)->cost ?? 0),
                'crew_names'     => $crewMap->get($p->id) ?? '',
                'open_punches'   => (int) ($openPunches->get($p->id)->c ?? 0),
                'daily_log_done' => $dailyLogs->has($p->id),
                'photos_today'   => (int) ($photos->get($p->id)->c ?? 0),
                'equipment_used' => (int) ($equipment->get($p->id)->c ?? 0),
                'open_rfis'      => (int) ($openRfis->get($p->id)->c ?? 0),
                'pending_cos'    => (int) ($pendingCos->get($p->id)->c ?? 0),
            ];
        });

        // Drop projects with zero today-activity AND zero open carry-overs —
        // no point spamming about a project that had a quiet day.
        $stats = $stats->filter(fn ($s) =>
            $s->hours > 0 || $s->open_punches > 0 || $s->photos_today > 0
            || $s->equipment_used > 0 || $s->open_rfis > 0 || $s->pending_cos > 0
        )->values();

        $this->info("Daily summary: {$stats->count()} active project(s) with activity today (out of {$projects->count()}).");

        if ($stats->isEmpty()) {
            $this->line('Nothing to send — every active project was quiet today.');
            return self::SUCCESS;
        }

        $recipients = User::notifiableForRoles([User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER]);
        if ($recipients->isEmpty()) {
            $this->warn('No active admin / PM users with email — skipping.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line('--dry-run: would email ' . $recipients->count() . ' user(s):');
            $recipients->each(fn ($u) => $this->line("  - {$u->name} <{$u->email}>"));
            return self::SUCCESS;
        }

        Notification::send($recipients, new ProjectDailySummary($stats, $today->format('M j, Y')));
        $this->info('Summary sent to ' . $recipients->count() . ' user(s).');

        return self::SUCCESS;
    }
}
