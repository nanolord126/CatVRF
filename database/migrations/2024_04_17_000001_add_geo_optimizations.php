<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add spatial optimizations for geolocation queries
     */
    public function up(): void
    {
        // Add spatial index to delivery_tracks if not exists
        Schema::table('delivery_tracks', function (Blueprint $table) {
            $table->index(['courier_id', 'tracked_at'], 'idx_courier_time');
            $table->spatialIndex('location', 'idx_location_spatial');
        });

        // Add spatial index to user_addresses
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->spatialIndex('coordinates', 'idx_address_spatial');
            $table->index(['user_id', 'usage_count'], 'idx_user_usage');
        });

        // Create materialized view for nearby items (PostgreSQL specific)
        DB::statement("
            CREATE MATERIALIZED VIEW IF NOT EXISTS nearby_providers_mv AS
            SELECT 
                id,
                provider_type,
                lat,
                lon,
                ST_Distance_Sphere(
                    ST_MakePoint(lon, lat),
                    ST_MakePoint(0, 0)
                ) / 1000 as distance_km
            FROM providers
            WHERE is_active = true
            WITH DATA;
        ");

        // Create index on materialized view
        DB::statement("CREATE INDEX IF NOT EXISTS idx_nearby_providers_distance ON nearby_providers_mv (distance_km)");

        // Create refresh function
        DB::statement("
            CREATE OR REPLACE FUNCTION refresh_nearby_providers()
            RETURNS void AS $$
            BEGIN
                REFRESH MATERIALIZED VIEW CONCURRENTLY nearby_providers_mv;
            END;
            $$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        Schema::table('delivery_tracks', function (Blueprint $table) {
            $table->dropIndex('idx_courier_time');
            $table->dropSpatialIndex('idx_location_spatial');
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropSpatialIndex('idx_address_spatial');
            $table->dropIndex('idx_user_usage');
        });

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS nearby_providers_mv');
        DB::statement('DROP FUNCTION IF EXISTS refresh_nearby_providers');
    }
};
