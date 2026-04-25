<?php

namespace App\Observers;

use App\Models\Rfi;
use App\Models\User;
use App\Notifications\RfiSubmitted;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies the assignee + admins/PMs when an RFI lands in 'submitted' state.
 * Drafts don't notify anyone (the worker hasn't asked the question yet).
 */
class RfiObserver
{
    public function created(Rfi $rfi): void
    {
        if ($rfi->status === 'submitted') {
            $this->dispatch($rfi);
        }
    }

    public function updated(Rfi $rfi): void
    {
        if ($rfi->wasChanged('status') && $rfi->status === 'submitted') {
            $this->dispatch($rfi);
        }
    }

    private function dispatch(Rfi $rfi): void
    {
        // Always include admins + PMs; if the RFI has a specific assignee,
        // include them too (de-duped via collection unique).
        $recipients = User::notifiableForRoles([
            User::ROLE_ADMIN,
            User::ROLE_PROJECT_MANAGER,
        ]);

        if ($rfi->assigned_to) {
            $assignee = User::find($rfi->assigned_to);
            if ($assignee && $assignee->is_active && !empty($assignee->email)) {
                $recipients = $recipients->push($assignee)->unique('id');
            }
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new RfiSubmitted($rfi));
    }
}
