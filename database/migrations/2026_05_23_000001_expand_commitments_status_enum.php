<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 2026-05-23 (KH): commitments.status was an ENUM with only
 * (pending, approved, completed, cancelled). KH wants two more values
 * — pending_signature + executed — so the contract lifecycle is
 * tracked end-to-end. MODIFY COLUMN to widen the enum.
 *
 * Raw SQL because Laravel's schema builder doesn't support modifying
 * enum values cleanly on MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE commitments MODIFY COLUMN status ENUM('pending','pending_signature','executed','approved','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Map any rows on the new values back to 'pending' so the narrower
        // enum below doesn't trip when re-applying it.
        DB::statement("UPDATE commitments SET status = 'pending' WHERE status IN ('pending_signature','executed')");
        DB::statement("ALTER TABLE commitments MODIFY COLUMN status ENUM('pending','approved','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
