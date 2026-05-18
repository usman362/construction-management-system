<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-12 (Brenda — Phase 5 / WhatsApp + SMS bot).
 *
 * TimeClockEntry was NOT NULL on user_id because every entry up to now
 * was created from a logged-in user's session. SMS / WhatsApp clock-ins
 * don't have a logged-in user — the Twilio router only has the Employee.
 * Make user_id nullable so bot-originated entries can save without
 * inventing a fake user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_clock_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('time_clock_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
