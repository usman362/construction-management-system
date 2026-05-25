<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 2026-05-23 (Brenda): change_orders.status only had
 * (pending, approved, rejected, voided). Brenda's screenshot shows
 * her desired five: Pending, Approved, Revising, Cancelled, Potential.
 *
 * We add the three new values (revising, cancelled, potential) and
 * KEEP the legacy 'rejected' + 'voided' in the enum so existing rows
 * don't break and reports that filter on them still work. The Edit
 * modal only surfaces her five — legacy values just stay readable
 * when they appear on old data.
 *
 * Raw SQL because Laravel's schema builder doesn't handle MySQL enum
 * widening cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE change_orders MODIFY COLUMN status ENUM('pending','approved','revising','cancelled','potential','rejected','voided') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Map any rows on the new values back to safe legacy ones so the
        // narrower enum below doesn't reject them.
        DB::statement("UPDATE change_orders SET status = 'pending' WHERE status IN ('revising','potential')");
        DB::statement("UPDATE change_orders SET status = 'voided'  WHERE status = 'cancelled'");
        DB::statement("ALTER TABLE change_orders MODIFY COLUMN status ENUM('pending','approved','rejected','voided') NOT NULL DEFAULT 'pending'");
    }
};
