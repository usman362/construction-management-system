<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-23 (KH WBS sheet): each estimate line needs a per-line BILLABLE
 * checkbox. Non-billable lines stay in the cost total but get excluded
 * from the billable / price total — e.g. "Misc Consumables" is a real
 * cost we eat but never pass through to the client.
 *
 * Default true (most lines are billable) so existing rows don't lose
 * their pricing on the rollout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->boolean('is_billable')->default(true)->after('markup_amount');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_lines', function (Blueprint $table) {
            $table->dropColumn('is_billable');
        });
    }
};
