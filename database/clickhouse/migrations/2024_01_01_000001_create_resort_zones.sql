-- Resort Zones Classification for Logistics Optimization
-- Created: April 19, 2026
-- Purpose: Classify resort/spit/beach zones for adaptive batching and routing

-- Drop existing tables
DROP TABLE IF EXISTS ch_resort_zones ON CLUSTER default;
DROP TABLE IF EXISTS ch_resort_zone_polygons ON CLUSTER default;
DROP TABLE IF EXISTS ch_resort_coastline ON CLUSTER default;
DROP TABLE IF EXISTS ch_zone_classification_mv ON CLUSTER default;

-- Main resort zones table with precomputed classifications
CREATE TABLE ch_resort_zones (
    zone_id UUID,
    tenant_id UInt32,
    zone_name String,
    zone_type Enum8('beach' = 1, 'spit' = 2, 'resort_base' = 3, 'coastal_road' = 4),
    
    -- Geographic data
    center_lat Float64,
    center_lng Float64,
    polygon_geojson String,  -- GeoJSON polygon for zone boundaries
    linear_density_score Float32,  -- How linear the zone is (0-1, higher = more linear/spit-like)
    coastline_distance_km Float32,  -- Distance to coastline
    
    -- Classification features
    is_resort_spit UInt8,  -- Binary flag for ML models
    max_batch_distance_km Float32,  -- Adaptive max distance for batching (1.5km for spit, 0.8-1.2km for city)
    deadhead_ratio_threshold Float32,  -- Max allowed deadhead ratio (0.12 for resort, 0.08 for city)
    
    -- Seasonal data
    is_seasonal UInt8,
    season_start_month UInt8,  -- May = 5
    season_end_month UInt8,    -- September = 9
    peak_hours Array(UInt8),   -- Hours when demand peaks (e.g., [10, 11, 12, 13, 14, 15, 16])
    
    -- Courier type preferences
    preferred_types Array(String),  -- ['pedestrian', 'scooter', 'ebike', 'car']
    pedestrian_max_radius_km Float32,  -- Max radius for pedestrians in this zone
    pedestrian_heat_limit_celsius Float32,  -- Heat limit for pedestrians (e.g., 35°C)
    
    -- Statistics
    avg_orders_per_hour Float32,
    avg_orders_per_km2 Float32,
    last_updated DateTime,
    
    -- Indices
    INDEX idx_tenant_zone (tenant_id, zone_type) TYPE minmax GRANULARITY 8192,
    INDEX idx_geo (center_lat, center_lng) TYPE minmax GRANULARITY 8192,
    INDEX idx_seasonal (is_seasonal, season_start_month) TYPE minmax GRANULARITY 8192,
    INDEX idx_is_resort_spit (is_resort_spit) TYPE set(1) GRANULARITY 8192,
    
    COMMENT 'Precomputed resort zone classifications for adaptive logistics'
) ENGINE = MergeTree()
ORDER BY (tenant_id, zone_type, center_lat, center_lng)
PARTITION BY toYYYYMM(last_updated)
TTL last_updated + INTERVAL 90 DAY;

-- Detailed polygon data for spatial queries
CREATE TABLE ch_resort_zone_polygons (
    zone_id UUID,
    tenant_id UInt32,
    point_index UInt32,
    lat Float64,
    lng Float64,
    is_coastline UInt8,  -- Whether this point is on coastline
    
    INDEX idx_zone_point (zone_id, point_index) TYPE minmax GRANULARITY 8192,
    
    COMMENT 'Polygon vertices for resort zones'
) ENGINE = MergeTree()
ORDER BY (zone_id, point_index)
PARTITION BY tenant_id;

-- Coastline layer for proximity detection
CREATE TABLE ch_resort_coastline (
    segment_id UUID,
    tenant_id UInt32,
    start_lat Float64,
    start_lng Float64,
    end_lat Float64,
    end_lng Float64,
    coastline_type Enum8('beach' = 1, 'spit' = 2, 'bay' = 3, 'mainland' = 4),
    
    INDEX idx_tenant_type (tenant_id, coastline_type) TYPE minmax GRANULARITY 8192,
    INDEX idx_geo_start (start_lat, start_lng) TYPE minmax GRANULARITY 8192,
    
    COMMENT 'Coastline segments for resort zone detection'
) ENGINE = MergeTree()
ORDER BY (tenant_id, coastline_type, start_lat, start_lng)
PARTITION BY tenant_id
TTL now() + INTERVAL 365 DAY;

-- Materialized view for automatic zone classification from order density
-- This analyzes order patterns and auto-classifies zones as resort/spit
CREATE MATERIALIZED VIEW ch_zone_classification_mv TO ch_resort_zones AS
SELECT
    toUUID(concat(toString(tenant_id), '_', toString(floor(center_lat * 100)), '_', toString(floor(center_lng * 100)))) AS zone_id,
    tenant_id,
    concat('zone_', toString(floor(center_lat * 100)), '_', toString(floor(center_lng * 100))) AS zone_name,
    multiIf(
        coastline_distance_km < 0.5 AND linear_density_score > 0.7, 'spit',
        coastline_distance_km < 1.0 AND avg_orders_per_km2 < 15, 'beach',
        coastline_distance_km < 2.0, 'resort_base',
        'coastal_road'
    ) AS zone_type,
    center_lat,
    center_lng,
    polygon_geojson,
    linear_density_score,
    coastline_distance_km,
    multiIf(
        zone_type = 'spit', 1,
        zone_type = 'beach', 1,
        0
    ) AS is_resort_spit,
    multiIf(
        zone_type = 'spit', 1.5,
        zone_type = 'beach', 1.2,
        0.8
    ) AS max_batch_distance_km,
    multiIf(
        zone_type IN ('spit', 'beach'), 0.12,
        0.08
    ) AS deadhead_ratio_threshold,
    1 AS is_seasonal,
    5 AS season_start_month,  -- May
    9 AS season_end_month,    -- September
    multiIf(
        zone_type = 'beach', [10, 11, 12, 13, 14, 15, 16],
        [9, 10, 11, 12, 13, 14, 15, 16, 17]
    ) AS peak_hours,
    multiIf(
        zone_type = 'beach', ['pedestrian', 'scooter'],
        ['scooter', 'ebike', 'car']
    ) AS preferred_types,
    multiIf(
        zone_type = 'beach', 0.5,
        0.8
    ) AS pedestrian_max_radius_km,
    35.0 AS pedestrian_heat_limit_celsius,
    avg_orders_per_hour,
    avg_orders_per_km2,
    now() AS last_updated
FROM (
    SELECT
        tenant_id,
        avg(latitude) AS center_lat,
        avg(longitude) AS center_lng,
        '' AS polygon_geojson,
        -- Calculate linear density: ratio of points along a line vs. area coverage
        -- Higher values indicate linear/spit-like geography
        case 
            when stddev(latitude) / (stddev(longitude) + 0.001) > 3.0 then 0.9
            when stddev(latitude) / (stddev(longitude) + 0.001) > 2.0 then 0.7
            when stddev(latitude) / (stddev(longitude) + 0.001) > 1.5 then 0.5
            else 0.3
        end AS linear_density_score,
        -- Estimate coastline distance (in production, use actual coastline data)
        min(
            pointInRing(
                (latitude, longitude),
                [(45.0, 36.5), (45.0, 37.0), (45.5, 37.0), (45.5, 36.5), (45.0, 36.5)]
            ) ? 0.1 : 5.0,
            pointInRing(
                (latitude, longitude),
                [(44.5, 37.0), (44.5, 38.0), (45.0, 38.0), (45.0, 37.0), (44.5, 37.0)]
            ) ? 0.2 : 5.0
        ) AS coastline_distance_km,
        count(*) / 24.0 AS avg_orders_per_hour,
        count(*) / (pow(max(latitude) - min(latitude) + 0.01, 2) * 111.0 * pow(max(longitude) - min(longitude) + 0.01, 2) * 111.0) AS avg_orders_per_km2
    FROM ch_geo_events
    WHERE event_type = 'click'
      AND created_at >= now() - INTERVAL 7 DAY
    GROUP BY 
        tenant_id,
        toStartOfInterval(latitude, 0.01) AS lat_bucket,
        toStartOfInterval(longitude, 0.01) AS lng_bucket
    HAVING count(*) >= 10  -- Minimum orders to form a zone
);

-- Insert sample popular resort spits (e.g., Anapa, Gelendzhik, Sochi areas)
INSERT INTO ch_resort_zones (zone_id, tenant_id, zone_name, zone_type, center_lat, center_lng, polygon_geojson, linear_density_score, coastline_distance_km, is_resort_spit, max_batch_distance_km, deadhead_ratio_threshold, is_seasonal, season_start_month, season_end_month, peak_hours, preferred_types, pedestrian_max_radius_km, pedestrian_heat_limit_celsius, avg_orders_per_hour, avg_orders_per_km2, last_updated) VALUES
-- Anapa Spit (Анафская коса)
(toUUID('00000000-0000-0000-0000-000000000001'), 1, 'anapa_spit', 'spit', 45.0, 37.3, '{"type":"Polygon","coordinates":[[[45.0,37.2],[45.0,37.4],[45.1,37.4],[45.1,37.2],[45.0,37.2]]]}', 0.95, 0.05, 1, 1.5, 0.12, 1, 5, 9, [10,11,12,13,14,15,16], ['pedestrian','scooter','ebike'], 0.8, 35.0, 25.0, 8.0, now()),
-- Gelendzhik Bay
(toUUID('00000000-0000-0000-0000-000000000002'), 1, 'gelendzhik_bay', 'beach', 44.5, 38.0, '{"type":"Polygon","coordinates":[[[44.5,37.9],[44.5,38.1],[44.6,38.1],[44.6,37.9],[44.5,37.9]]]}', 0.6, 0.1, 1, 1.2, 0.12, 1, 5, 9, [10,11,12,13,14,15,16], ['pedestrian','scooter'], 0.5, 35.0, 30.0, 12.0, now()),
-- Sochi Coast
(toUUID('00000000-0000-0000-0000-000000000003'), 1, 'sochi_coast', 'coastal_road', 43.6, 39.7, '{"type":"Polygon","coordinates":[[[43.5,39.6],[43.5,39.8],[43.7,39.8],[43.7,39.6],[43.5,39.6]]]}', 0.4, 0.2, 0, 1.0, 0.10, 1, 5, 9, [9,10,11,12,13,14,15,16,17], ['scooter','ebike','car'], 0.8, 35.0, 45.0, 20.0, now()),
-- Sample city zone (non-resort) for comparison
(toUUID('00000000-0000-0000-0000-000000000004'), 1, 'krasnodar_center', 'coastal_road', 45.0, 38.9, '{"type":"Polygon","coordinates":[[[45.0,38.8],[45.0,39.0],[45.1,39.0],[45.1,38.8],[45.0,38.8]]]}', 0.2, 50.0, 0, 0.8, 0.08, 0, 0, 0, [9,10,11,12,13,14,15,16,17,18], ['scooter','ebike','car'], 0.8, 35.0, 80.0, 150.0, now());

-- Create function to check if a point is in a resort zone
CREATE OR REPLACE FUNCTION isResortZone(lat Float64, lng Float64, tenantId UInt32) AS
    SELECT count() > 0 FROM ch_resort_zones
    WHERE tenant_id = tenantId
      AND pointInPolygon((lat, lng), polygon_geojson)
      AND is_resort_spit = 1
      AND (
          NOT is_seasonal
          OR (month(now()) >= season_start_month AND month(now()) <= season_end_month)
      );

-- Create function to get max batch distance for a zone
CREATE OR REPLACE FUNCTION getMaxBatchDistance(lat Float64, lng Float64, tenantId UInt32) AS
    SELECT coalesce(max(max_batch_distance_km), 0.8) FROM ch_resort_zones
    WHERE tenant_id = tenantId
      AND pointInPolygon((lat, lng), polygon_geojson);

-- Create function to get deadhead ratio threshold for a zone
CREATE OR REPLACE FUNCTION getDeadheadThreshold(lat Float64, lng Float64, tenantId UInt32) AS
    SELECT coalesce(max(deadhead_ratio_threshold), 0.08) FROM ch_resort_zones
    WHERE tenant_id = tenantId
      AND pointInPolygon((lat, lng), polygon_geojson);

-- Health check
SELECT 'ClickHouse Resort Zones Schema Installation Complete' AS status,
       count() AS total_zones,
       countIf(is_resort_spit = 1) AS resort_spit_zones,
       now() AS created_at
FROM ch_resort_zones;
