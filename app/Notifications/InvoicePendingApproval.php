<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to accountants/admins when a vendor invoice is created and awaits
 * approval before payment. Queued.
 */
class InvoicePendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inv = $this->invoice->loadMissing(['vendor', 'project', 'costCode']);
        $url = url('/invoices/' . $inv->id);

        $details = [
            'Invoice #'   => $inv->invoice_number ?? '—',
            'Vendor'      => $inv->vendor->name ?? '—',
            'Project'     => $inv->project ? (($inv->project->project_number ?? '') . ' — ' . $inv->project->name) : '—',
            'Amount'      => '$' . number_format((float) $inv->amount, 2),
            'Date'        => optional($inv->invoice_date)->format('M j, Y') ?? '—',
            'Due'         => optional($inv->due_date)->format('M j, Y') ?? '—',
            'Cost Code'   => $inv->costCode ? ($inv->costCode->code . ' — ' . $inv->costCode->name) : '—',
        ];

        return (new MailMessage())
            ->subject('Invoice pending approval — ' . ($inv->vendor->name ?? 'Vendor'))
            ->view('emails.layout', [
                'subject'    => 'Invoice pending approval',
                'greeting'   => 'Hi ' . ($notifiable->name ?? '') . ',',
                'intro'      => 'A new vendor invoice is waiting on your approval before payment.',
                'details'    => $details,
                'actionUrl'  => $url,
                'actionText' => 'Review Invoice',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'invoice_pending',
            'invoice_id' => $this->invoice->id,
            'vendor'     => optional($this->invoice->vendor)->name,
            'amount'     => (float) $this->invoice->amount,
            'url'        => '/invoices/' . $this->invoice->id,
        ];
    }
}
