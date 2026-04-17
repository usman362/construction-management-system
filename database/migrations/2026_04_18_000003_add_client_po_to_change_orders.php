<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: "Change order preview needs the client po added."
 * Add a client_po column to change_orders so it can be captured + printed
 * on the CO preview / approval form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_orders', function (Blueprint $table) {
            $table->string('client_po', 100)->nullable()->after('co_number');
        });
    }

    public function down(): void
    {
        Schema::table('change_orders', function (Blueprint $table) {
            $table->dropColumn('client_po');
        });
    }
};
