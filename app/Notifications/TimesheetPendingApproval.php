<?php

namespace App\Notifications;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to admins/PMs when a worker submits a timesheet that needs approval.
 * Queued so the timesheet save isn't blocked by mail latency.
 */
class TimesheetPendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Timesheet $timesheet) {}

    public function via(object $notifiable): array
    {
        // Mail today; database channel piggybacks an in-app notification list later.
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ts = $this->timesheet->loadMissing(['employee', 'project', 'costCode']);
        $url = url('/timesheets/' . $ts->id);

        $details = [
            'Employee' => trim(($ts->employee->first_name ?? '') . ' ' . ($ts->employee->last_name ?? '')) ?: '—',
            'Project'  => ($ts->project->project_number ?? '—') . ' — ' . ($ts->project->name ?? ''),
            'Date'     => optional($ts->date)->format('M j, Y') ?? '—',
            'Hours'    => sprintf('%.2f regular · %.2f OT · %.2f total',
                                  (float) $ts->regular_hours,
                                  (float) $ts->overtime_hours,
                                  (float) $ts->total_hours),
            'Cost Code' => $ts->costCode ? ($ts->costCode->code . ' — ' . $ts->costCode->name) : '— not assigned —',
        ];

        return (new MailMessage())
            ->subject('Timesheet pending approval — ' . ($ts->employee->first_name ?? 'Worker'))
            ->view('emails.layout', [
                'subject'    => 'Timesheet pending approval',
                'greeting'   => 'Hi ' . ($notifiable->name ?? '') . ',',
                'intro'      => 'A timesheet has been submitted and is waiting on your approval.',
                'details'    => $details,
                'actionUrl'  => $url,
                'actionText' => 'Review Timesheet',
                'footer'     => 'You can also see all pending timesheets in the dashboard under Pending Approvals.',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'timesheet_pending',
            'timesheet_id' => $this->timesheet->id,
            'employee'     => optional($this->timesheet->employee)->first_name . ' ' . optional($this->timesheet->employee)->last_name,
            'project'      => optional($this->timesheet->project)->project_number,
            'url'          => '/timesheets/' . $this->timesheet->id,
        ];
    }
}
