<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Project-level default retainage % — copied onto each new billing invoice.
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('retainage_percent', 5, 2)->default(0)->after('contract_value');
        });

        // Per-invoice retainage snapshot so rate history is preserved even if the project default changes.
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->decimal('retainage_percent', 5, 2)->default(0)->after('tax_amount');
            $table->decimal('retainage_amount', 15, 2)->default(0)->after('retainage_percent');
            $table->boolean('retainage_released')->default(false)->after('retainage_amount');
            $table->date('retainage_released_date')->nullable()->after('retainage_released');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('retainage_percent');
        });
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->dropColumn(['retainage_percent', 'retainage_amount', 'retainage_released', 'retainage_released_date']);
        });
    }
};
