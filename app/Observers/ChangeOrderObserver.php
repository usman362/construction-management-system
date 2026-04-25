<?php

namespace App\Observers;

use App\Models\ChangeOrder;
use App\Models\User;
use App\Notifications\ChangeOrderPending;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies admins + PMs when a change order moves into 'pending' state.
 * Skips drafts (PM hasn't asked for sign-off yet).
 */
class ChangeOrderObserver
{
    public function created(ChangeOrder $co): void
    {
        if ($co->status === 'pending') {
            $this->dispatch($co);
        }
    }

    public function updated(ChangeOrder $co): void
    {
        if ($co->wasChanged('status') && $co->status === 'pending') {
            $this->dispatch($co);
        }
    }

    private function dispatch(ChangeOrder $co): void
    {
        $recipients = User::notifiableForRoles([
            User::ROLE_ADMIN,
            User::ROLE_PROJECT_MANAGER,
        ]);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ChangeOrderPending($co));
    }
}
