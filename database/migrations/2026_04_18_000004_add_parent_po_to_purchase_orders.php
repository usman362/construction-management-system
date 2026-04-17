<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: "Can the purchase orders have a change order? (i.e. BM-6625-01)"
 * Allow a PO to reference a parent PO (for CO-style amendments that sit
 * beneath the original PO, e.g. BM-6625 → BM-6625-01). Also link a PO
 * directly to its originating ChangeOrder if there is one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('parent_po_id')
                ->nullable()
                ->after('project_id')
                ->constrained('purchase_orders')
                ->nullOnDelete();

            $table->foreignId('change_order_id')
                ->nullable()
                ->after('parent_po_id')
                ->constrained('change_orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['parent_po_id']);
            $table->dropColumn('parent_po_id');
            $table->dropForeign(['change_order_id']);
            $table->dropColumn('change_order_id');
        });
    }
};
