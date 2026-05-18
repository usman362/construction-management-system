<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * End-of-day project digest (Brenda — Phase 4, 2026-05-12).
 *
 * Sent by the scheduled `projects:daily-summary` command at 5pm each
 * weekday. One email per PM/Admin user bundling every active project's
 * today rollup so they don't have to chase per-project emails.
 *
 * Each project row carries: labor hours + cost booked today, crew on
 * site (open punches + people who clocked any hours today), daily-log
 * status (logged or not), photo upload count, equipment used, and a
 * small "open RFIs / pending COs" tail.
 */
class ProjectDailySummary extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Collection $projectStats Array of stat objects, one per project.
     *                                 Each has: project, hours, cost, crew_names,
     *                                 daily_log_done, photos_today, equipment_used,
     *                                 open_rfis, pending_cos.
     */
    public function __construct(public Collection $projectStats, public string $forDate) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $totalHours = (float) $this->projectStats->sum('hours');
        $totalCost  = (float) $this->projectStats->sum('cost');
        $activeP    = $this->projectStats->filter(fn ($s) => $s->hours > 0)->count();

        $rows = $this->projectStats->map(function ($s) {
            $logBadge = $s->daily_log_done
                ? '<span style="background:#dcfce7; color:#166534; padding:1px 6px; border-radius:3px; font-size:11px; font-weight:600;">log ✓</span>'
                : '<span style="background:#fee2e2; color:#991b1b; padding:1px 6px; border-radius:3px; font-size:11px; font-weight:600;">no log</span>';

            $hourBlock = $s->hours > 0
                ? '<strong>' . number_format($s->hours, 1) . ' hrs</strong> · $' . number_format($s->cost, 0)
                : '<span style="color:#9ca3af;">no labor</span>';

            $crewLine = $s->crew_names
                ? '<div style="font-size:12px; color:#6b7280; margin-top:4px;">Crew: ' . e($s->crew_names) . '</div>'
                : '';

            $tail = [];
            if ($s->photos_today > 0)   $tail[] = $s->photos_today . ' photo' . ($s->photos_today === 1 ? '' : 's');
            if ($s->equipment_used > 0) $tail[] = $s->equipment_used . ' piece(s) of equipment';
            if ($s->open_rfis > 0)      $tail[] = $s->open_rfis . ' open RFI' . ($s->open_rfis === 1 ? '' : 's');
            if ($s->pending_cos > 0)    $tail[] = $s->pending_cos . ' pending CO' . ($s->pending_cos === 1 ? '' : 's');

            $tailLine = $tail
                ? '<div style="font-size:12px; color:#6b7280; margin-top:2px;">' . e(implode(' · ', $tail)) . '</div>'
                : '';

            return '<div style="border-bottom:1px solid #e5e7eb; padding:10px 0;">'
                . '<div style="display:flex; justify-content:space-between; align-items:baseline;">'
                . '<div><strong style="font-family:monospace;">' . e($s->project->project_number ?? '—') . '</strong> '
                . '<span style="color:#374151;">' . e($s->project->name ?? '') . '</span> ' . $logBadge . '</div>'
                . '<div>' . $hourBlock . '</div>'
                . '</div>'
                . $crewLine
                . $tailLine
                . '</div>';
        })->implode('');

        $body = '<div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:16px;">'
            . '<div style="font-size:13px; color:#6b7280; margin-bottom:8px;">Across ' . $activeP . ' active project(s)</div>'
            . '<div style="display:flex; gap:24px;">'
            . '<div><div style="font-size:11px; color:#9ca3af; text-transform:uppercase;">Hours</div><strong style="font-size:18px;">' . number_format($totalHours, 1) . '</strong></div>'
            . '<div><div style="font-size:11px; color:#9ca3af; text-transform:uppercase;">Labor cost</div><strong style="font-size:18px;">$' . number_format($totalCost, 0) . '</strong></div>'
            . '</div>'
            . '</div>'
            . '<div style="margin-top:16px;">' . $rows . '</div>';

        return (new MailMessage())
            ->subject('Daily project summary — ' . $this->forDate)
            ->view('emails.layout', [
                'subject'    => 'Daily project summary',
                'greeting'   => 'Hi ' . ($notifiable->name ?? '') . ',',
                'intro'      => 'Here\'s where every active project landed today.',
                'body'       => $body,
                'actionUrl'  => url('/dashboard'),
                'actionText' => 'Open dashboard',
                'footer'     => 'You receive this because you have project-manager or admin access. To stop these emails, ask your administrator.',
            ]);
    }
}
