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
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('cost_code_id')->constrained('cost_codes')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->decimal('budget_amount', 15, 2)->default(0);
            $table->decimal('revised_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'cost_code_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
