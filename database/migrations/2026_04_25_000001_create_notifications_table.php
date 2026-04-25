<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standard Laravel notifications table.
 *
 * Notification classes that include 'database' in their via() array write a
 * row here, which powers the in-app bell/notification list (read/unread,
 * grouped per user) without sending an email at all.
 *
 * Email channel and database channel are independent — every notification
 * we send currently uses both, so users get an email AND see it in their
 * in-app inbox.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');           // notifiable_type + notifiable_id
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
