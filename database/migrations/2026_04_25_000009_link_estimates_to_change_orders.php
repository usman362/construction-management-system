<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link an estimate to a specific Change Order on a project (Brenda 04.25.2026:
 * "the smaller one [estimating module] for change orders inside the project").
 *
 * The schema already has estimate_type which can hold 'change_order' as a
 * value. This migration adds the foreign-key column so a CO-scoped estimate
 * can deep-link back to its source CO without parsing the description string.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->foreignId('change_order_id')
                ->nullable()
                ->after('converted_to_project_id')
                ->constrained('change_orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('change_order_id');
        });
    }
};
