<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tool & small-equipment tracking — saws, drills, ladders, anything below
 * the threshold that warrants a full Equipment record. The split keeps the
 * Equipment table focused on bigger rentable / billable assets, while Tools
 * track the daily checkout/checkin flow with a simpler schema.
 *
 * Two tables:
 *   - tools             — the catalog (one row per physical tool)
 *   - tool_assignments  — open + historical issuance log (who, when, return)
 *
 * Tools also support QR codes (same UUID-token pattern as Equipment) so the
 * same scan UI can be reused.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('asset_tag', 50)->nullable()->unique();   // sticker # or company asset id
            $table->string('category', 50)->nullable();              // power, hand, ladder, ppe, etc.
            $table->string('serial_number', 100)->nullable();
            $table->string('qr_token', 36)->nullable()->unique();
            $table->decimal('replacement_cost', 10, 2)->nullable();  // for billing if lost
            $table->enum('status', ['available', 'issued', 'lost', 'retired'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('status');
        });

        Schema::create('tool_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('issued_date');
            $table->date('due_back_date')->nullable();      // expected return date
            $table->date('returned_date')->nullable();       // actual return date (null = still out)
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tool_id', 'returned_date']);
            $table->index(['employee_id', 'returned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_assignments');
        Schema::dropIfExists('tools');
    }
};
