<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Performance indexes to speed up common queries.
     * Fixes 504 Gateway Timeout issues caused by slow DB lookups.
     */
    public function up(): void
    {
        // Projects — status filter used on dashboard & lists
        Schema::table('projects', function (Blueprint $table) {
            $table->index('status');
            $table->index('client_id');
            $table->index(['status', 'created_at']);
        });

        // Timesheets — heavily queried with filters
        Schema::table('timesheets', function (Blueprint $table) {
            $table->index('status');
            $table->index('project_id');
            $table->index('employee_id');
            $table->index('cost_code_id');
            $table->index('date');
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'status', 'date']);
            $table->index(['employee_id', 'date']);
        });

        // Employees — status filter
        Schema::table('employees', function (Blueprint $table) {
            $table->index('status');
            $table->index('craft_id');
        });

        // Change Orders — status filter per project
        Schema::table('change_orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('project_id');
            $table->index(['project_id', 'status']);
        });

        // Commitments — grouped by cost_code_id per project
        Schema::table('commitments', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('cost_code_id');
            $table->index(['project_id', 'cost_code_id']);
        });

        // Invoices — grouped by cost_code_id per project
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('cost_code_id');
            $table->index('commitment_id');
        });

        // Budget Lines — per project with cost code
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('cost_code_id');
            $table->index(['project_id', 'cost_code_id']);
        });

        // Cost Codes — hierarchical lookups
        Schema::table('cost_codes', function (Blueprint $table) {
            $table->index('parent_id');
            $table->index('is_active');
        });

        // Payroll Entries
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->index('employee_id');
            $table->index('project_id');
            $table->index('payroll_period_id');
        });

        // Billing Invoices
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('status');
        });

        // Equipment Assignments — current assignment lookups
        Schema::table('equipment_assignments', function (Blueprint $table) {
            $table->index('equipment_id');
            $table->index('project_id');
            $table->index(['equipment_id', 'returned_date']);
        });

        // Material Usages
        Schema::table('material_usages', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('material_id');
        });

        // Crews
        Schema::table('crews', function (Blueprint $table) {
            $table->index('project_id');
        });

        // Manhour Budgets
        Schema::table('manhour_budgets', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('cost_code_id');
        });

        // Daily Logs
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('date');
        });

        // Users — role and active status for middleware checks
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_active');
            $table->index(['role', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['client_id']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['cost_code_id']);
            $table->dropIndex(['date']);
            $table->dropIndex(['project_id', 'status']);
            $table->dropIndex(['project_id', 'status', 'date']);
            $table->dropIndex(['employee_id', 'date']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['craft_id']);
        });

        Schema::table('change_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['project_id', 'status']);
        });

        Schema::table('commitments', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['cost_code_id']);
            $table->dropIndex(['project_id', 'cost_code_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['cost_code_id']);
            $table->dropIndex(['commitment_id']);
        });

        Schema::table('budget_lines', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['cost_code_id']);
            $table->dropIndex(['project_id', 'cost_code_id']);
        });

        Schema::table('cost_codes', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['payroll_period_id']);
        });

        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('equipment_assignments', function (Blueprint $table) {
            $table->dropIndex(['equipment_id']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['equipment_id', 'returned_date']);
        });

        Schema::table('material_usages', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['material_id']);
        });

        Schema::table('crews', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
        });

        Schema::table('manhour_budgets', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['cost_code_id']);
        });

        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['date']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['role', 'is_active']);
        });
    }
};
