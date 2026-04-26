<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7F — E-signature capture for Change Orders + Lien Waivers.
 *
 * Each table gets the same trio of columns the Timesheet schema uses:
 *   - signature           longText (Base64-encoded PNG data URL from canvas)
 *   - signature_name      string  (typed name of signer; legal "I am" line)
 *   - signed_at           timestamp (when capture happened)
 *
 * Stored inline (not as files) so the data lives next to the document it
 * signs and can be embedded directly into PDF exports without an extra
 * Storage::get() round-trip.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('change_orders', function (Blueprint $table) {
            // signed_by — optional user FK so we know who hit the "Sign" button
            // (typically the PM or admin approving). For client-signed COs the
            // name field is the legal record; user FK is for our audit trail.
            $table->longText('signature')->nullable()->after('approved_date');
            $table->string('signature_name', 150)->nullable()->after('signature');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
            $table->foreignId('signed_by')->nullable()->after('signed_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('lien_waivers', function (Blueprint $table) {
            $table->longText('signature')->nullable()->after('notes');
            $table->string('signature_name', 150)->nullable()->after('signature');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
            $table->foreignId('signed_by')->nullable()->after('signed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('change_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_by');
            $table->dropColumn(['signature', 'signature_name', 'signed_at']);
        });

        Schema::table('lien_waivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_by');
            $table->dropColumn(['signature', 'signature_name', 'signed_at']);
        });
    }
};
