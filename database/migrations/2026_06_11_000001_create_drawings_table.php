<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drawing Log — Brenda asked (2026-06-11) for Procore-style drawings:
 * upload a sheet, system tracks revisions, new rev of the same sheet
 * number auto-marks the prior one as superseded.
 *
 * sheet_number + project + status='current' is unique — only one
 * "current" version of a sheet exists at a time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('sheet_number', 50)->index();
            $table->string('sheet_title', 255);
            $table->string('discipline', 50)->nullable()->index();
            $table->string('revision', 20)->default('0');
            $table->string('status', 20)->default('current')->index();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('file_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('superseded_by_id')->nullable()->constrained('drawings')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'sheet_number', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawings');
    }
};
