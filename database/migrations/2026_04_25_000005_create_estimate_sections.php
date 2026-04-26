<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logical groupings inside an estimate (Sitework, Foundation, Framing, etc.).
 * Estimate lines belong to a section so the bid PDF can render readable
 * subtotals per phase rather than a flat list of 80 line items.
 *
 * Section subtotals (cost_amount / price_amount) are recalculated by the
 * EstimateLineObserver each time a line under that section changes.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('estimate_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained('estimates')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('cost_amount', 15, 2)->default(0);
            $table->decimal('price_amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['estimate_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_sections');
    }
};
