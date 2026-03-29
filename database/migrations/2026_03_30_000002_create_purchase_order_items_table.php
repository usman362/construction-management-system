<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit_of_measure')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('received_quantity', 10, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'received', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
