<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Unified Logistics Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for unified logistics system (courier + taxi + PVZ).
    | Follows CatVRF production standards.
    |
    */

    'fleet' => [
        /*
         * Search radius for courier discovery (in km)
         */
        'search_radius_km' => config('LOGISTICS_FLEET_SEARCH_RADIUS', 5.0),

        /*
         * Maximum candidates to return for assignment
         */
        'max_candidates' => config('LOGISTICS_FLEET_MAX_CANDIDATES', 20),

        /*
         * Minimum battery level for electric vehicles (%)
         */
        'min_battery_level' => config('LOGISTICS_FLEET_MIN_BATTERY', 20),

        /*
         * Vehicle speed assumptions for ETA calculation (km/h)
         */
        'vehicle_speeds' => [
            'pedestrian' => 5,
            'bike' => 15,
            'scooter' => 20,
            'car' => 30,
            'taxi' => 35,
        ],

        /*
         * Scoring weights for courier selection
         * Sum should equal 1.0
         */
        'scoring_weights' => [
            'distance' => 0.4,
            'rating' => 0.3,
            'availability' => 0.2,
            'taxi_bonus' => 0.1,
        ],

        /*
         * Taxi driver bonus for time-sensitive orders
         */
        'taxi_time_sensitive_bonus' => 0.1,

        /*
         * Battery penalty for low battery electric vehicles
         */
        'low_battery_penalty' => 0.1,
    ],

    'pvz' => [
        /*
         * Search radius for PVZ discovery (in meters)
         */
        'search_radius_meters' => config('LOGISTICS_PVZ_SEARCH_RADIUS', 3000),

        /*
         * Maximum load percentage before considering PVZ overloaded
         */
        'max_load_percentage' => config('LOGISTICS_PVZ_MAX_LOAD', 85),

        /*
         * Hold duration for PVZ reservations (in minutes)
         */
        'hold_duration_minutes' => config('LOGISTICS_PVZ_HOLD_DURATION', 20),

        /*
         * Maximum candidates to return for PVZ assignment
         */
        'max_candidates' => config('LOGISTICS_PVZ_MAX_CANDIDATES', 15),

        /*
         * Scoring weights for PVZ selection
         * Sum should equal 1.0
         */
        'scoring_weights' => [
            'proximity' => 0.4,
            'load_balance' => 0.3,
            'user_history' => 0.2,
            'predicted_demand' => 0.1,
        ],

        /*
         * Load balance preferences
         */
        'load_balance' => [
            'optimal_min' => 40,  // % - prefer PVZs above this
            'optimal_max' => 70,  // % - prefer PVZs below this
            'near_overload' => 85, // % - warning threshold
        ],

        /*
         * Peak hours for demand prediction
         */
        'peak_hours' => [
            'morning_start' => 10,
            'morning_end' => 14,
            'evening_start' => 17,
            'evening_end' => 20,
        ],

        /*
         * Late night hours for 24/7 PVZ preference
         */
        'late_night_start' => 21,
        'late_night_end' => 8,

        /*
         * Late night bonus for 24/7 PVZs
         */
        'late_night_bonus' => 0.15,

        /*
         * User preference cache TTL (in hours)
         */
        'user_preference_cache_hours' => 24,
    ],

    'shipment' => [
        /*
         * Default pickup time buffer for PVZ ETA (in minutes)
         */
        'pvz_pickup_buffer' => 5,

        /*
         * Extra time for busy PVZs (in minutes)
         */
        'busy_pvz_extra_time' => 10,

        /*
         * Walking speed for PVZ ETA calculation (km/h)
         */
        'walking_speed_kmh' => 5,
    ],

    'queue' => [
        /*
         * Queue name for logistics jobs
         */
        'queue_name' => config('LOGISTICS_QUEUE', 'logistics'),

        /*
         * Job timeout for PVZ issuance (in seconds)
         */
        'pvz_issuance_timeout' => config('LOGISTICS_PVZ_ISSUANCE_TIMEOUT', 120),

        /*
         * Job retry attempts
         */
        'pvz_issuance_tries' => config('LOGISTICS_PVZ_ISSUANCE_TRIES', 3),

        /*
         * Unique job lock duration (in seconds)
         */
        'pvz_issuance_unique_for' => 3600,
    ],

    'cache' => [
        /*
         * Cache tags for invalidation
         */
        'tags' => [
            'pvz_holds',
            'courier_locations',
            'pvz_availability',
        ],

        /*
         * Distance calculation cache TTL (in seconds)
         */
        'distance_cache_ttl' => 300,

        /*
         * User preference cache TTL (in seconds)
         */
        'user_preference_cache_ttl' => 86400, // 24 hours
    ],
];
