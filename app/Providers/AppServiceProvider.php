<?php

namespace App\Providers;

use App\Models\ChangeOrder;
use App\Models\Invoice;
use App\Models\Rfi;
use App\Models\Timesheet;
use App\Observers\ChangeOrderObserver;
use App\Observers\InvoiceObserver;
use App\Observers\RfiObserver;
use App\Observers\TimesheetObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ─── Notification observers ─────────────────────────────────
        // Each observer dispatches a queued email + database notification
        // when a record moves into a "needs attention" state. Wired here
        // (rather than in the model's booted()) so the event hook lives in
        // one place we can audit / disable globally if needed.
        Timesheet::observe(TimesheetObserver::class);
        Rfi::observe(RfiObserver::class);
        ChangeOrder::observe(ChangeOrderObserver::class);
        Invoice::observe(InvoiceObserver::class);
    }
}
