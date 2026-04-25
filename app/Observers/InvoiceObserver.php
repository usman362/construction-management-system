<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoicePendingApproval;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies admins + accountants when a vendor invoice is created or moves
 * into 'pending' state. Approved/paid invoices are silent — they've already
 * been acted on.
 */
class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        if ($invoice->status === 'pending') {
            $this->dispatch($invoice);
        }
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->wasChanged('status') && $invoice->status === 'pending') {
            $this->dispatch($invoice);
        }
    }

    private function dispatch(Invoice $invoice): void
    {
        $recipients = User::notifiableForRoles([
            User::ROLE_ADMIN,
            User::ROLE_ACCOUNTANT,
        ]);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new InvoicePendingApproval($invoice));
    }
}
