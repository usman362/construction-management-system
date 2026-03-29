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
        Schema::create('change_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('co_number');
            $table->date('date');
            $table->text('description')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'voided'])->default('pending');
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('contract_time_change_days')->default(0);
            $table->date('new_completion_date')->nullable();
            $table->string('approved_by')->nullable();
            $table->date('approved_date')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'co_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_orders');
    }
};
