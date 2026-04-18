<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client feedback (Notes to programmer, 04.19.26):
 * "Add a dropdown on the change order for Lump Sum and T & M"
 *
 * Distinguishes fixed-price change orders (lump_sum) from time-and-materials
 * change orders (t_and_m), mirroring standard construction contract terms.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('change_orders', function (Blueprint $table) {
            $table->enum('pricing_type', ['lump_sum', 't_and_m'])
                ->default('lump_sum')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('change_orders', function (Blueprint $table) {
            $table->dropColumn('pricing_type');
        });
    }
};
