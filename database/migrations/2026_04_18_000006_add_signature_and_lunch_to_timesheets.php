<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client field timesheet requirements:
 *   - Nucor: already has gate_log_hours; add work_through_lunch checkbox
 *   - iPad/print signature: add client signature capture
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->boolean('work_through_lunch')->default(false)->after('gate_log_hours');
            // Base64 data URL of the signature drawn on iPad, or a stored file path.
            $table->longText('client_signature')->nullable()->after('work_through_lunch');
            $table->string('client_signature_name', 150)->nullable()->after('client_signature');
            $table->timestamp('signed_at')->nullable()->after('client_signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropColumn(['work_through_lunch', 'client_signature', 'client_signature_name', 'signed_at']);
        });
    }
};
