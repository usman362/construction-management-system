<?php

namespace App\Notifications;

use App\Models\Rfi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the RFI's assigned reviewer (and optionally the PM) when a field
 * worker submits a new RFI on a project. Queued.
 */
class RfiSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Rfi $rfi) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rfi = $this->rfi->loadMissing(['project', 'submitter', 'assignee']);
        $url = url('/projects/' . $rfi->project_id . '/rfis/' . $rfi->id);

        $details = [
            'RFI #'      => $rfi->rfi_number ?? '—',
            'Project'    => ($rfi->project->project_number ?? '—') . ' — ' . ($rfi->project->name ?? ''),
            'Subject'    => $rfi->subject ?? '—',
            'Submitted by' => $rfi->submitter->name ?? '—',
            'Priority'   => ucfirst($rfi->priority ?? 'normal'),
            'Category'   => ucfirst(str_replace('_', ' ', $rfi->category ?? '—')),
            'Needed by'  => $rfi->needed_by ? \Carbon\Carbon::parse($rfi->needed_by)->format('M j, Y') : 'No date set',
        ];

        return (new MailMessage())
            ->subject('New RFI — ' . ($rfi->rfi_number ?? '') . ' · ' . ($rfi->subject ?? ''))
            ->view('emails.layout', [
                'subject'    => 'New RFI submitted',
                'greeting'   => 'Hi ' . ($notifiable->name ?? '') . ',',
                'intro'      => 'A new RFI has been submitted and is waiting on your response.',
                'details'    => $details,
                'body'       => $rfi->question
                    ? '<strong>Question:</strong><br>' . nl2br(e(\Illuminate\Support\Str::limit($rfi->question, 400)))
                    : null,
                'actionUrl'  => $url,
                'actionText' => 'Open RFI',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'rfi_submitted',
            'rfi_id'  => $this->rfi->id,
            'number'  => $this->rfi->rfi_number,
            'subject' => $this->rfi->subject,
            'project' => optional($this->rfi->project)->project_number,
            'url'     => '/projects/' . $this->rfi->project_id . '/rfis/' . $this->rfi->id,
        ];
    }
}
