<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Equipment rental expiry alert (Brenda 04.28.2026).
 *
 * Single morning digest grouping all rentals that hit a 7-day / 3-day /
 * 1-day / overdue threshold. Bundled (one email per recipient) so the
 * inbox doesn't get flooded — same pattern as the cert expiry digest.
 */
class RentalExpiringAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Collection $overdue   EquipmentAssignment past due
     * @param Collection $in1       Due tomorrow (≤1 day out)
     * @param Collection $in3       Due in 2-3 days
     * @param Collection $in7       Due in 4-7 days
     */
    public function __construct(
        public Collection $overdue,
        public Collection $in1,
        public Collection $in3,
        public Collection $in7,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/equipment/rental-calendar');
        $totalCount = $this->overdue->count() + $this->in1->count() + $this->in3->count() + $this->in7->count();

        $sections = [];
        if ($this->overdue->count()) $sections[] = $this->renderSection('🔴 Already PAST DUE', $this->overdue, true);
        if ($this->in1->count())     $sections[] = $this->renderSection('🟠 Due tomorrow', $this->in1);
        if ($this->in3->count())     $sections[] = $this->renderSection('🟡 Due in 2-3 days', $this->in3);
        if ($this->in7->count())     $sections[] = $this->renderSection('🔵 Due in 4-7 days', $this->in7);

        $body = empty($sections)
            ? '<p style="margin:0; color:#10b981;">No rentals approaching their off-rent date. 🎉</p>'
            : implode('', $sections);

        return (new MailMessage())
            ->subject('Equipment rental watch — ' . $totalCount . ' approaching off-rent date')
            ->view('emails.layout', [
                'subject'    => 'Equipment rental watch',
                'greeting'   => 'Hi ' . ($notifiable->name ?? '') . ',',
                'intro'      => 'These rentals are approaching their off-rent dates. Return them on time to avoid extra rental charges.',
                'body'       => $body,
                'actionUrl'  => $url,
                'actionText' => 'Open Rental Calendar',
                'footer'     => 'You receive this because you have admin or PM access. Configure expected return dates when you check equipment out via the QR scanner.',
            ]);
    }

    private function renderSection(string $title, Collection $rows, bool $isOverdue = false): string
    {
        $items = $rows->map(function ($a) use ($isOverdue) {
            $name = $a->equipment->name ?? 'Equipment';
            $proj = $a->project->project_number ?? '—';
            $due  = optional($a->expected_return_date)->format('M j, Y') ?? '—';
            $vendor = $a->equipment->vendor->name ?? null;

            $line = '<strong>' . e($name) . '</strong> — '
                . 'Project <span style="font-family:monospace;">' . e($proj) . '</span>'
                . ' — Due ' . e($due);
            if ($vendor) $line .= ' — Vendor ' . e($vendor);

            if ($isOverdue) {
                $today = \Carbon\Carbon::today();
                $days  = (int) floor($a->expected_return_date->diffInDays($today));
                $line .= ' <span style="color:#dc2626;">(' . $days . ' day' . ($days === 1 ? '' : 's') . ' overdue)</span>';
            }
            return '<li style="margin:6px 0; font-size:14px; color:#374151;">' . $line . '</li>';
        })->implode('');

        return '<div style="margin:16px 0;">'
            . '<h3 style="margin:0 0 8px; font-size:14px; color:#111827;">' . e($title) . ' (' . $rows->count() . ')</h3>'
            . '<ul style="margin:0; padding-left:20px;">' . $items . '</ul>'
            . '</div>';
    }
}
