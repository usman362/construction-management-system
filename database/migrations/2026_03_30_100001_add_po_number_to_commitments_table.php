<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commitments', function (Blueprint $table) {
            $table->string('po_number', 50)->nullable()->after('commitment_number');
        });

        // Make cost_code_id nullable if it isn't already
        Schema::table('commitments', function (Blueprint $table) {
            $table->foreignId('cost_code_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('commitments', function (Blueprint $table) {
            $table->dropColumn('po_number');
        });
    }
};
