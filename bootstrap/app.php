<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // ─── Phase 7A: Notification schedule ────────────────────────
        // Morning digest of everything that needs attention. Runs Mon-Fri at
        // 7am — a single weekday morning summary so the user starts each day
        // knowing where to focus, without weekend noise.
        $schedule->command('digest:send')
            ->weekdays()
            ->timezone(config('app.timezone'))
            ->at('07:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Cert expiry — now runs every weekday morning at 8am (was twice
        // weekly). Per-employee milestone notices need daily cadence to
        // catch the 7-day mark accurately; the per-cert notice flags
        // prevent re-sending the same milestone, so the digest still
        // reads "weekly-ish" in spirit (one email per cert per milestone).
        // Brenda — Phase 1, 2026-05-12.
        $schedule->command('certs:notify-expiring')
            ->weekdays()
            ->at('08:00')
            ->timezone(config('app.timezone'))
            ->withoutOverlapping()
            ->onOneServer();

        // Equipment rental off-rent alerts (Brenda 04.28.2026) — daily 7:30am
        // weekdays. Catches anything coming due in the next 7 days plus
        // anything already overdue.
        $schedule->command('equipment:rental-expiry-alerts')
            ->weekdays()
            ->at('07:30')
            ->timezone(config('app.timezone'))
            ->withoutOverlapping()
            ->onOneServer();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'active' => \App\Http\Middleware\CheckActiveUser::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\CheckActiveUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
