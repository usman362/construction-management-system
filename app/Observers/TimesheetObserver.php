<?php

namespace App\Observers;

use App\Models\Timesheet;
use App\Models\User;
use App\Notifications\TimesheetPendingApproval;
use Illuminate\Support\Facades\Notification;

/**
 * Fires email + in-app alerts for timesheet status changes.
 *
 * - On creation with status='pending': notify admins + PMs
 * - On updated to status='pending' (e.g. re-submission after rejection):
 *   notify admins + PMs
 *
 * Notifications are queued, so the timesheet save itself never blocks on
 * mail latency.
 */
class TimesheetObserver
{
    public function created(Timesheet $timesheet): void
    {
        // Timesheet workflow: draft → submitted → approved/rejected.
        // "Submitted" is the moment the worker has finished entry and asks for
        // approval — that's when the approver should be pinged.
        if ($timesheet->status === 'submitted') {
            $this->dispatch($timesheet);
        }
    }

    public function updated(Timesheet $timesheet): void
    {
        // Only fire on a true status transition INTO 'submitted' — not on every
        // edit (otherwise approvers get spammed when a worker fixes a typo).
        if ($timesheet->wasChanged('status') && $timesheet->status === 'submitted') {
            $this->dispatch($timesheet);
        }
    }

    private function dispatch(Timesheet $timesheet): void
    {
        $recipients = User::notifiableForRoles([
            User::ROLE_ADMIN,
            User::ROLE_PROJECT_MANAGER,
        ]);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TimesheetPendingApproval($timesheet));
    }
}
