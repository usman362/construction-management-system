<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();   // "01", "02", "11", "14"
            $table->string('name', 100);             // "Direct Labor", "Materials"
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the 12 fixed cost types from the client's WBS document.
        DB::table('cost_types')->insert([
            ['code' => '01', 'name' => 'Direct Labor',              'sort_order' => 1,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '02', 'name' => 'Materials',                 'sort_order' => 2,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '03', 'name' => '3rd Party Rental Equipment', 'sort_order' => 3,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '04', 'name' => 'Company Owned',             'sort_order' => 4,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '05', 'name' => 'Field Tools and Supplies',  'sort_order' => 5,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '06', 'name' => 'Subcontractors',            'sort_order' => 6,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '07', 'name' => 'PER DIEM',                  'sort_order' => 7,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '08', 'name' => 'Non-Reimburseable',         'sort_order' => 8,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '11', 'name' => 'Indirect Labor',            'sort_order' => 9,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '12', 'name' => 'FREIGHT',                   'sort_order' => 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '13', 'name' => 'Sales Tax',                 'sort_order' => 11, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '14', "name" => "Travel Fee's",              'sort_order' => 12, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_types');
    }
};
