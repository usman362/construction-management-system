<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lien_waivers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('commitment_id')->nullable()->constrained('commitments')->nullOnDelete();

            // 4 standard lien waiver types (progress vs final × conditional vs unconditional).
            $table->enum('type', [
                'conditional_progress',
                'unconditional_progress',
                'conditional_final',
                'unconditional_final',
            ])->default('conditional_progress');

            $table->decimal('amount', 15, 2)->default(0);   // Dollar amount the waiver covers.
            $table->date('through_date')->nullable();        // Waiver covers payment through this date.
            $table->date('received_date')->nullable();       // Date the signed waiver came back.

            $table->enum('status', ['pending', 'received', 'rejected'])->default('pending')->index();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lien_waivers');
    }
};
