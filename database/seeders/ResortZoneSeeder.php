<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Logistics\Models\ResortZone;
use Illuminate\Database\Seeder;

final class ResortZoneSeeder extends Seeder
{
    public function run(): void
    {
        ResortZone::query()->truncate();

        ResortZone::create([
            'tenant_id' => 1,
            'zone_name' => 'Anapa Spit (Анафская коса)',
            'zone_type' => 'spit',
            'polygon_geojson' => '{"type":"Polygon","coordinates":[[[45.0,37.2],[45.0,37.4],[45.1,37.4],[45.1,37.2],[45.0,37.2]]]}',
            'linear_density_score' => 0.95,
            'coastline_distance_km' => 0.05,
            'is_resort_spit' => true,
            'max_batch_distance_km' => 1.5,
            'deadhead_ratio_threshold' => 0.12,
            'is_seasonal' => true,
            'season_start_month' => 5,
            'season_end_month' => 9,
            'peak_hours' => [10, 11, 12, 13, 14, 15, 16],
            'preferred_types' => ['pedestrian', 'scooter', 'ebike'],
            'pedestrian_max_radius_km' => 0.8,
            'pedestrian_heat_limit_celsius' => 35.0,
            'avg_orders_per_hour' => 25.0,
            'avg_orders_per_km2' => 8.0,
        ]);

        ResortZone::create([
            'tenant_id' => 1,
            'zone_name' => 'Gelendzhik Bay',
            'zone_type' => 'beach',
            'polygon_geojson' => '{"type":"Polygon","coordinates":[[[44.5,37.9],[44.5,38.1],[44.6,38.1],[44.6,37.9],[44.5,37.9]]]}',
            'linear_density_score' => 0.6,
            'coastline_distance_km' => 0.1,
            'is_resort_spit' => true,
            'max_batch_distance_km' => 1.2,
            'deadhead_ratio_threshold' => 0.12,
            'is_seasonal' => true,
            'season_start_month' => 5,
            'season_end_month' => 9,
            'peak_hours' => [10, 11, 12, 13, 14, 15, 16],
            'preferred_types' => ['pedestrian', 'scooter'],
            'pedestrian_max_radius_km' => 0.5,
            'pedestrian_heat_limit_celsius' => 35.0,
            'avg_orders_per_hour' => 30.0,
            'avg_orders_per_km2' => 12.0,
        ]);

        ResortZone::create([
            'tenant_id' => 1,
            'zone_name' => 'Sochi Coast',
            'zone_type' => 'coastal_road',
            'polygon_geojson' => '{"type":"Polygon","coordinates":[[[43.5,39.6],[43.5,39.8],[43.7,39.8],[43.7,39.6],[43.5,39.6]]]}',
            'linear_density_score' => 0.4,
            'coastline_distance_km' => 0.2,
            'is_resort_spit' => false,
            'max_batch_distance_km' => 1.0,
            'deadhead_ratio_threshold' => 0.10,
            'is_seasonal' => true,
            'season_start_month' => 5,
            'season_end_month' => 9,
            'peak_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
            'preferred_types' => ['scooter', 'ebike', 'car'],
            'pedestrian_max_radius_km' => 0.8,
            'pedestrian_heat_limit_celsius' => 35.0,
            'avg_orders_per_hour' => 45.0,
            'avg_orders_per_km2' => 20.0,
        ]);

        ResortZone::create([
            'tenant_id' => 1,
            'zone_name' => 'Krasnodar Center (non-resort)',
            'zone_type' => 'coastal_road',
            'polygon_geojson' => '{"type":"Polygon","coordinates":[[[45.0,38.8],[45.0,39.0],[45.1,39.0],[45.1,38.8],[45.0,38.8]]]}',
            'linear_density_score' => 0.2,
            'coastline_distance_km' => 50.0,
            'is_resort_spit' => false,
            'max_batch_distance_km' => 0.8,
            'deadhead_ratio_threshold' => 0.08,
            'is_seasonal' => false,
            'season_start_month' => 0,
            'season_end_month' => 0,
            'peak_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
            'preferred_types' => ['scooter', 'ebike', 'car'],
            'pedestrian_max_radius_km' => 0.8,
            'pedestrian_heat_limit_celsius' => 35.0,
            'avg_orders_per_hour' => 80.0,
            'avg_orders_per_km2' => 150.0,
        ]);

        $this->command->info('Resort zones seeded successfully');
    }
}