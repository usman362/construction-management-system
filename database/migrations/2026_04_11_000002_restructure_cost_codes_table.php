<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplify cost_codes per client's WBS feedback:
 * - Drop: category, parent_id, description, sort_order, cost_type (varchar)
 * - Add: cost_type_id FK → cost_types.id
 * - Keep: code, name, is_active, timestamps
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: add new FK column
        Schema::table('cost_codes', function (Blueprint $table) {
            $table->foreignId('cost_type_id')
                ->nullable()
                ->after('name')
                ->constrained('cost_types')
                ->nullOnDelete();
        });

        // Step 2: best-effort backfill — match old cost_type VARCHAR or category to new cost_types by name
        if (Schema::hasColumn('cost_codes', 'cost_type')) {
            $costTypes = DB::table('cost_types')->pluck('id', 'name')
                ->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id]);

            $rows = DB::table('cost_codes')->select('id', 'cost_type', 'category', 'name')->get();
            foreach ($rows as $row) {
                $candidates = [
                    $row->cost_type,
                    $row->category,
                    $row->name,
                ];
                foreach ($candidates as $cand) {
                    if (empty($cand)) continue;
                    $key = strtolower(trim($cand));
                    if (isset($costTypes[$key])) {
                        DB::table('cost_codes')->where('id', $row->id)->update(['cost_type_id' => $costTypes[$key]]);
                        break;
                    }
                }
            }
        }

        // Step 3: drop the legacy columns
        Schema::table('cost_codes', function (Blueprint $table) {
            // Drop parent FK first (if it exists) before dropping the column
            try { $table->dropForeign(['parent_id']); } catch (\Throwable $e) { /* may not exist */ }
        });

        Schema::table('cost_codes', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['category', 'cost_type', 'parent_id', 'description', 'sort_order'] as $col) {
                if (Schema::hasColumn('cost_codes', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('cost_codes', function (Blueprint $table) {
            $table->dropForeign(['cost_type_id']);
            $table->dropColumn('cost_type_id');

            // Restore legacy columns
            $table->string('category', 50)->nullable();
            $table->string('cost_type', 50)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('cost_codes')->nullOnDelete();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
        });
    }
};
