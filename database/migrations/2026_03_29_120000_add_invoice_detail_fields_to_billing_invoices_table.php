<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->date('due_date')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
        });

        DB::table('billing_invoices')->whereNull('invoice_date')->update([
            'invoice_date' => DB::raw('billing_period_start'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->dropColumn(['invoice_date', 'due_date', 'description', 'notes']);
        });
    }
};
