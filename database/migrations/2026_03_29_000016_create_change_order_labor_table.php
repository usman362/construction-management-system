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
        Schema::create('change_order_labor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_order_id')->constrained('change_orders')->cascadeOnDelete();
            $table->foreignId('craft_id')->nullable()->constrained('crafts')->nullOnDelete();
            $table->string('skill_description');
            $table->integer('num_workers')->default(1);
            $table->decimal('rate_per_hour', 10, 2)->default(0);
            $table->decimal('hours_per_day', 4, 2)->default(0);
            $table->decimal('duration_days', 6, 2)->default(0);
            $table->boolean('is_overtime')->default(false);
            $table->decimal('cost', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_order_labor');
    }
};
