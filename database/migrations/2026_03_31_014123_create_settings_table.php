<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'company_name', 'value' => 'BuildTrack', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_tagline', 'value' => 'Construction Mgmt', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_logo', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'favicon', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'primary_color', 'value' => '#2563eb', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
