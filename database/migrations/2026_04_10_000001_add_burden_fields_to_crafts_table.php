<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crafts', function (Blueprint $table) {
            $table->decimal('ot_billable_rate', 10, 2)->nullable()->after('billable_rate');
            $table->decimal('wc_rate', 8, 4)->nullable()->after('ot_billable_rate');
            $table->decimal('fica_rate', 8, 4)->nullable()->after('wc_rate');
            $table->decimal('suta_rate', 8, 4)->nullable()->after('fica_rate');
            $table->decimal('benefits_rate', 10, 2)->nullable()->after('suta_rate');
            $table->decimal('overhead_rate', 8, 4)->nullable()->after('benefits_rate');
        });
    }

    public function down(): void
    {
        Schema::table('crafts', function (Blueprint $table) {
            $table->dropColumn([
                'ot_billable_rate', 'wc_rate', 'fica_rate',
                'suta_rate', 'benefits_rate', 'overhead_rate',
            ]);
        });
    }
};
