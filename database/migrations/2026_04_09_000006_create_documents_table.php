<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->enum('category', [
                'proposal', 'photo', 'change_order', 'purchase_order',
                'delivery_ticket', 'estimate', 'daily_log', 'report',
                'correspondence', 'contract', 'permit', 'insurance', 'other',
            ])->default('other');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id', 'category'], 'docs_morph_category_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
