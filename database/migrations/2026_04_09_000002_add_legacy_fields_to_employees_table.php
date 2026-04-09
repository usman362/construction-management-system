<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Legacy identifiers (from previous payroll / scheduling system)
            $table->string('legacy_employee_id', 50)->nullable()->after('employee_number')->index();
            $table->string('legacy_position', 100)->nullable()->after('legacy_employee_id');
            $table->string('legacy_craft', 100)->nullable()->after('legacy_position');

            // Name
            $table->string('middle_name', 100)->nullable()->after('first_name');

            // Address breakdown
            $table->string('address_1', 255)->nullable()->after('phone');
            $table->string('address_2', 255)->nullable()->after('address_1');
            $table->string('city', 100)->nullable()->after('address_2');
            $table->string('state', 50)->nullable()->after('city');
            $table->string('zip', 20)->nullable()->after('state');

            // Multiple phones
            $table->string('home_phone', 30)->nullable()->after('zip');
            $table->string('work_cell', 30)->nullable()->after('home_phone');
            $table->string('personal_cell', 30)->nullable()->after('work_cell');

            // Payroll fields
            $table->enum('pay_cycle', ['weekly', 'bi_weekly', 'semi_monthly', 'monthly'])->default('weekly')->after('billable_rate');
            $table->enum('pay_type', ['hourly', 'salary'])->default('hourly')->after('pay_cycle');
            $table->string('union', 100)->nullable()->after('pay_type');
            $table->string('employee_type', 100)->nullable()->after('union'); // Operator, Laborer, etc
            $table->string('department', 100)->nullable()->after('employee_type');
            $table->string('classification', 100)->nullable()->after('department');
            $table->boolean('is_supervisor')->default(false)->after('classification');
            $table->boolean('certified_pay')->default(false)->after('is_supervisor');

            // Tax / comp
            $table->string('work_comp_code', 50)->nullable()->after('certified_pay');
            $table->string('suta_state', 10)->nullable()->after('work_comp_code');
            $table->string('state_tax', 10)->nullable()->after('suta_state');
            $table->string('city_tax', 50)->nullable()->after('state_tax');
            $table->decimal('burden_rate', 5, 2)->nullable()->after('city_tax');

            // Employment dates
            $table->date('start_date')->nullable()->after('hire_date');
            $table->date('rehire_date')->nullable()->after('start_date');
            $table->date('term_date')->nullable()->after('rehire_date');
            $table->text('term_reason')->nullable()->after('term_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'legacy_employee_id', 'legacy_position', 'legacy_craft',
                'middle_name',
                'address_1', 'address_2', 'city', 'state', 'zip',
                'home_phone', 'work_cell', 'personal_cell',
                'pay_cycle', 'pay_type', 'union', 'employee_type', 'department',
                'classification', 'is_supervisor', 'certified_pay',
                'work_comp_code', 'suta_state', 'state_tax', 'city_tax', 'burden_rate',
                'start_date', 'rehire_date', 'term_date', 'term_reason',
            ]);
        });
    }
};
