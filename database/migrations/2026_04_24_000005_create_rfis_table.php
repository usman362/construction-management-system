<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // Auto-numbered per project — "RFI-0001", "RFI-0002", ... — assigned in the model on create.
            $table->string('rfi_number', 50);
            $table->unique(['project_id', 'rfi_number']);

            $table->string('subject');
            $table->text('question');                             // The actual question being asked.
            $table->text('response')->nullable();                 // The answer.
            $table->text('cost_schedule_impact')->nullable();     // Narrative of any impact discovered while answering.

            // Workflow status — RFIs move from submitted → in_review → answered → closed.
            // Separate "answered" from "closed" so the originator can confirm the answer before closing.
            $table->enum('status', ['draft', 'submitted', 'in_review', 'answered', 'closed'])
                ->default('draft')
                ->index();

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->index();

            // Category helps filter (e.g. drawings vs specs vs field condition).
            $table->enum('category', [
                'drawings',
                'specifications',
                'scope',
                'schedule',
                'field_condition',
                'submittal',
                'other',
            ])->default('other');

            // Actors.
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();

            // Dates.
            $table->date('submitted_date')->nullable();   // Date formally submitted (not drafted).
            $table->date('needed_by')->nullable();        // When the answer is needed.
            $table->date('responded_date')->nullable();   // When the answer was provided.
            $table->date('closed_date')->nullable();

            // Impact flags — quick indicators without parsing the narrative.
            $table->boolean('cost_impact')->default(false);
            $table->boolean('schedule_impact')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfis');
    }
};
