<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add PO reference fields to billing invoices (Brenda 2026-04-30).
 *
 *   "Also when we enter a vendor invoice, can we add a po reference spot?"
 *
 * Two columns added so the office can either:
 *   - Pick an existing internal PO (purchase_order_id, FK), OR
 *   - Type a free-text PO number (po_reference) for vendor PO numbers /
 *     external POs that aren't in our system.
 *
 * Both are nullable — most legacy invoices won't have either.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('project_id')
                ->constrained('purchase_orders')->nullOnDelete()
                ->comment('Optional FK to an internal PO');
            $table->string('po_reference', 100)->nullable()->after('purchase_order_id')
                ->comment('Free-text PO number (vendor or external)');
        });
    }

    public function down(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn(['purchase_order_id', 'po_reference']);
        });
    }
};
