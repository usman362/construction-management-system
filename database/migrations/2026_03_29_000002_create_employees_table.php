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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->foreignId('craft_id')->nullable()->constrained('crafts')->nullOnDelete();
            $table->enum('role', ['field', 'foreman', 'superintendent', 'project_manager', 'admin', 'accounting']);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('overtime_rate', 10, 2);
            $table->decimal('billable_rate', 10, 2);
            $table->date('hire_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
