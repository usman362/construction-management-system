<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-07-01 QA pass — DB indexes for high-traffic filter/sort patterns.
 *
 *   invoices          filtered by (project_id, status)
 *   timesheets        filtered by (status), (project_id, status)
 *   billing_invoices  filtered by (status), (paid_date)
 *   change_orders     filtered by (status), (project_id, status)
 *   estimate_lines    filtered by (estimate_id) — very frequent
 *
 * Each add is idempotent via information_schema check so re-runs don't fail
 * on tables that already got these indexes from an earlier migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->maybeAddIndex('invoices',         ['project_id', 'status'], 'invoices_project_status_idx');
        $this->maybeAddIndex('timesheets',       ['status'],               'timesheets_status_idx');
        $this->maybeAddIndex('timesheets',       ['project_id', 'status'], 'timesheets_project_status_idx');
        $this->maybeAddIndex('billing_invoices', ['status'],               'billing_invoices_status_idx');
        $this->maybeAddIndex('billing_invoices', ['paid_date'],            'billing_invoices_paid_date_idx');
        $this->maybeAddIndex('change_orders',    ['status'],               'change_orders_status_idx');
        $this->maybeAddIndex('change_orders',    ['project_id', 'status'], 'change_orders_project_status_idx');
        $this->maybeAddIndex('estimate_lines',   ['estimate_id'],          'estimate_lines_estimate_id_idx');
    }

    public function down(): void
    {
        $this->maybeDropIndex('invoices',         'invoices_project_status_idx');
        $this->maybeDropIndex('timesheets',       'timesheets_status_idx');
        $this->maybeDropIndex('timesheets',       'timesheets_project_status_idx');
        $this->maybeDropIndex('billing_invoices', 'billing_invoices_status_idx');
        $this->maybeDropIndex('billing_invoices', 'billing_invoices_paid_date_idx');
        $this->maybeDropIndex('change_orders',    'change_orders_status_idx');
        $this->maybeDropIndex('change_orders',    'change_orders_project_status_idx');
        $this->maybeDropIndex('estimate_lines',   'estimate_lines_estimate_id_idx');
    }

    private function maybeAddIndex(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table)) return;
        if ($this->indexExists($table, $name)) return;
        Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
    }

    private function maybeDropIndex(string $table, string $name): void
    {
        if (!Schema::hasTable($table)) return;
        if (!$this->indexExists($table, $name)) return;
        Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $found = DB::selectOne(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
             LIMIT 1",
            [$db, $table, $indexName]
        );
        return $found !== null;
    }
};
