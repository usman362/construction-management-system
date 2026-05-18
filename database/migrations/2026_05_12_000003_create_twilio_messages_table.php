<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-12 (Brenda — Phase 5 / WhatsApp + SMS bot).
 *
 * Audit log of every incoming Twilio webhook. Lets us debug routing
 * decisions, replay failed messages, and link each message back to
 * whatever record (timesheet / daily log / time clock entry) it
 * produced.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twilio_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_sid', 60)->nullable()->index();
            $table->string('from_phone', 30)->index();
            $table->string('to_phone', 30)->nullable();
            $table->string('channel', 16)->default('sms')->comment('sms | whatsapp');
            $table->text('body')->nullable();
            $table->unsignedSmallInteger('num_media')->default(0);
            $table->json('media')->nullable()->comment('[{url, content_type}, ...]');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('intent', 40)->nullable()->comment('clock_in | clock_out | timesheet_photo | invoice_photo | daily_log_voice | help | unknown');
            $table->string('status', 20)->default('received')->comment('received | processed | failed');
            $table->text('reply')->nullable()->comment('What we replied with');
            $table->text('error')->nullable();
            $table->nullableMorphs('related');     // links to TimeClockEntry / Timesheet / DailyLog when applicable
            $table->json('raw_payload')->nullable()->comment('Full webhook payload for debugging');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twilio_messages');
    }
};
