<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for the Snap-a-Timesheet OCR feature (Brenda 04.29.2026).
 *
 * Every photo Brenda or her clerks upload through the AI scanner is logged
 * here with: who scanned it, the file path, the AI's raw extraction, and
 * the IDs of any timesheets that ended up being created. Two reasons:
 *
 *   1) Audit / dispute resolution — if a payroll line is wrong six weeks
 *      from now we can pull the original photo + AI output to investigate.
 *   2) Tuning — track confidence scores and per-row match status so we can
 *      eventually report how often the AI gets it right unattended.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('timesheet_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            // The Claude response, stored verbatim so we can replay later
            // if the parsing logic changes. Including raw lets us debug
            // edge cases without re-uploading the original file.
            $table->json('extracted_payload')->nullable();
            $table->json('raw_response')->nullable();
            // Once the user confirms and we bulk-create timesheets,
            // store the resulting timesheet IDs so we can link back.
            $table->json('created_timesheet_ids')->nullable();
            $table->string('status', 20)->default('extracted')
                ->comment('extracted | confirmed | discarded | failed');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_scan_logs');
    }
};
