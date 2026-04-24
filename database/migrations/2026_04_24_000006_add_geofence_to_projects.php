<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a geofence center + radius to each project.
     * Used by the mobile clock-in feature to flag (not block) punches
     * that happen outside the expected site. Field users in construction
     * often work in buffers — parking lots, staging areas, driving between
     * gates — so we only warn, we don't block.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Decimal precision chosen for ~1-cm accuracy: 9 digits, 6 after point.
            $table->decimal('latitude', 9, 6)->nullable()->after('zip');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
            // Radius in meters — default 300m (roughly 1 city block / typical jobsite footprint).
            $table->unsignedInteger('geofence_radius_m')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'geofence_radius_m']);
        });
    }
};
