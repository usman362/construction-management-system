<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Actor — snapshot user_name so the log still reads right if a user is later deleted.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();

            // Polymorphic target (Timesheet / ChangeOrder / Invoice / ...).
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->index(['auditable_type', 'auditable_id']);

            // What happened + what changed.
            $table->enum('event', ['created', 'updated', 'deleted', 'restored'])->index();
            $table->json('changes')->nullable(); // { field: [old, new] } for updates; full attrs for create/delete.

            // Context.
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // No updated_at — logs are append-only.
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
