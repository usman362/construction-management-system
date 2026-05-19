<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-12 (Brenda — Tools enhancement, 2026-05-18):
 *  - location: where the tool lives when not issued (Yard, Truck 5, Shop, etc.)
 *  - purchase_date / purchase_ticket_*: attach the original receipt /
 *    purchase ticket so warranty + audit trail lives on the tool record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->string('location', 150)->nullable()->after('category');
            $table->date('purchase_date')->nullable()->after('replacement_cost');
            $table->string('purchase_ticket_path')->nullable()->after('purchase_date');
            $table->string('purchase_ticket_name')->nullable()->after('purchase_ticket_path');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropIndex(['location']);
            $table->dropColumn(['location', 'purchase_date', 'purchase_ticket_path', 'purchase_ticket_name']);
        });
    }
};
