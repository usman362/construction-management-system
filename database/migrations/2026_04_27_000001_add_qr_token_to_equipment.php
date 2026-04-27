<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * QR-code-based check-in/out — Brenda's "Field Pack" item 3.
 *
 * Each piece of equipment gets a stable, unguessable token. The token is
 * encoded into the QR sticker stuck on the asset. Scanning the QR opens
 * /equipment/scan/{token} on the user's phone, where they can check the
 * unit out (assign to a project) or check it in (mark returned).
 *
 * Why a token, not the raw equipment id?
 *   - Tokens are unguessable (can't enumerate by URL)
 *   - Stickers stay valid forever even if the equipment id changes
 *   - Easy to revoke (set qr_token to null) if a sticker is lost
 *
 * Backfill: every existing equipment row gets a fresh token at migration time.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('qr_token', 36)->nullable()->unique()->after('serial_number');
        });

        // Backfill existing rows with a UUID. Doing it in one query keeps the
        // migration fast even on large fleets.
        $rows = DB::table('equipment')->whereNull('qr_token')->pluck('id');
        foreach ($rows as $id) {
            DB::table('equipment')->where('id', $id)->update(['qr_token' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
