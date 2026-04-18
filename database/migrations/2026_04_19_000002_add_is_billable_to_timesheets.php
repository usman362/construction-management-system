<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client feedback (Notes to programmer, 04.19.26):
 * "Billable check box is not staying checked."
 *
 * Previously the "Billable" checkbox was derived from billable_amount > 0,
 * which meant the user's explicit choice was lost on every save (the amount
 * gets recomputed from rates). This migration persists the user's intent in
 * a real column so the checkbox state survives edits.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->boolean('is_billable')->default(true)->after('billable_amount');
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropColumn('is_billable');
        });
    }
};
