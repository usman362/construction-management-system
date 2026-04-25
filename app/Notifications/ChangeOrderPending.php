<?php

namespace App\Notifications;

use App\Models\ChangeOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to admins/PMs when a change order moves into 'pending' status and
 * needs sign-off. Queued.
 */
class ChangeOrderPending extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ChangeOrder $changeOrder) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $co = $this->changeOrder->loadMissing(['project']);
        $url = url('/projects/' . $co->project_id . '/change-orders/' . $co->id);

        $details = [
            'CO #'        => $co->co_number ?? '—',
            'Project'     => ($co->project->project_number ?? '—') . ' — ' . ($co->project->name ?? ''),
            'Title'       => $co->title ?? '—',
            'Cost Impact' => '$' . number_format((float) $co->amount, 2),
            'Schedule Impact' => ($co->contract_time_change_days ?? 0) . ' days',
            'Status'      => ucfirst($co->status ?? '—'),
        ];

        return (new MailMessage())
            ->subject('Change Order pending approval — ' . ($co->co_number ?? ''))
            ->view('emails.layout', [
                'subject'    => 'Change Order pending approval',
                'greeting'   => 'Hi ' . ($notifiable->name ?? '') . ',',
                'intro'      => 'A change order has been submitted and is waiting on your approval.',
                'details'    => $details,
                'body'       => $co->description
                    ? '<strong>Description:</strong><br>' . nl2br(e(\Illuminate\Support\Str::limit($co->description, 400)))
                    : null,
                'actionUrl'  => $url,
                'actionText' => 'Review Change Order',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'change_order_pending',
            'change_order_id'  => $this->changeOrder->id,
            'co_number'        => $this->changeOrder->co_number,
            'amount'           => (float) $this->changeOrder->amount,
            'url'              => '/projects/' . $this->changeOrder->project_id . '/change-orders/' . $this->changeOrder->id,
        ];
    }
}
