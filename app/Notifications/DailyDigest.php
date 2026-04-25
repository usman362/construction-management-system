<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Single morning digest: pending timesheets, RFIs awaiting response, change
 * orders pending approval, invoices waiting, expired/expiring certs.
 *
 * Computed once per User (in DailyDigestCommand) and passed in pre-bucketed
 * so this class only renders.
 */
class DailyDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array $payload Keys:
     *   - pendingTimesheets:    Collection<Timesheet>
     *   - openRfis:             Collection<Rfi>
     *   - pendingChangeOrders:  Collection<ChangeOrder>
     *   - pendingInvoices:      Collection<Invoice>
     *   - expiredCerts:         Collection<EmployeeCertification>
     *   - expiringCerts30:      Collection<EmployeeCertification>
     *   - clockedInNow:         int
     *   - billedThisMonth:      float
     *   - collectedThisMonth:   float
     */
    public function __construct(public array $payload) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $p = $this->payload;
        $url = url('/');

        // Top-line counts the user can act on today.
        $counts = [
            'Timesheets pending approval' => $p['pendingTimesheets']->count(),
            'Open RFIs awaiting response' => $p['openRfis']->count(),
            'Change Orders pending'       => $p['pendingChangeOrders']->count(),
            'Invoices to approve'         => $p['pendingInvoices']->count(),
            'Certifications expired'      => $p['expiredCerts']->count(),
            'Certifications expiring ≤30d' => $p['expiringCerts30']->count(),
            'Clocked-in now'              => $p['clockedInNow'],
            'Billed this month'           => '$' . number_format($p['billedThisMonth'], 2),
            'Collected this month'        => '$' . number_format($p['collectedThisMonth'], 2),
        ];

        // Render counts as a 2-col HTML table so the email looks like a real
        // dashboard, not a wall of text.
        $rows = '';
        foreach ($counts as $label => $value) {
            $color = (is_numeric($value) && $value > 0)
                ? '#dc2626'
                : (str_starts_with((string) $value, '$') ? '#111827' : '#9ca3af');
            $rows .= '<tr>'
                . '<td style="padding:8px 12px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6;">' . e($label) . '</td>'
                . '<td style="padding:8px 12px; font-size:14px; font-weight:700; text-align:right; color:' . $color . '; border-bottom:1px solid #f3f4f6;">' . e($value) . '</td>'
                . '</tr>';
        }
        $body = '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb; border-radius:6px; overflow:hidden; margin:8px 0;">' . $rows . '</table>';

        // Add a quick "top items" preview block when there's anything urgent.
        $previews = [];
        if ($p['pendingTimesheets']->count()) {
            $previews[] = $this->renderPreview('Top pending timesheets',
                $p['pendingTimesheets']->take(5)->map(fn ($t) =>
                    (optional($t->employee)->first_name . ' ' . optional($t->employee)->last_name) .
                    ' — ' . optional($t->project)->project_number .
                    ' — ' . number_format((float) $t->total_hours, 2) . 'h'
                ));
        }
        if ($p['openRfis']->count()) {
            $previews[] = $this->renderPreview('Top open RFIs',
                $p['openRfis']->take(5)->map(fn ($r) =>
                    ($r->rfi_number ?? '—') . ' — ' . ($r->subject ?? '') .
                    ' (' . optional($r->project)->project_number . ')'
                ));
        }
        if ($p['pendingChangeOrders']->count()) {
            $previews[] = $this->renderPreview('Change Orders awaiting approval',
                $p['pendingChangeOrders']->take(5)->map(fn ($c) =>
                    ($c->co_number ?? '—') . ' — ' . ($c->title ?? '') .
                    ' ($' . number_format((float) $c->amount, 2) . ')'
                ));
        }
        $body .= implode('', $previews);

        return (new MailMessage())
            ->subject('Your BuildTrack morning digest — ' . now()->format('M j'))
            ->view('emails.layout', [
                'subject'    => 'Your morning digest',
                'greeting'   => 'Good morning ' . ($notifiable->name ?? '') . ',',
                'intro'      => 'Here is your snapshot of what needs attention today.',
                'body'       => $body,
                'actionUrl'  => $url,
                'actionText' => 'Open Dashboard',
                'footer'     => 'You receive this each weekday morning. Reply to your administrator to adjust frequency.',
            ]);
    }

    private function renderPreview(string $title, $items): string
    {
        if ($items->isEmpty()) return '';
        $li = $items->map(fn ($s) => '<li style="margin:4px 0; font-size:13px; color:#374151;">' . e($s) . '</li>')->implode('');
        return '<div style="margin:16px 0;">'
            . '<h4 style="margin:0 0 6px; font-size:13px; color:#111827; text-transform:uppercase; letter-spacing:0.05em;">' . e($title) . '</h4>'
            . '<ul style="margin:0; padding-left:20px;">' . $li . '</ul>'
            . '</div>';
    }
}
