<?php

declare(strict_types=1);

/**
 * Residential Proxy Detection Configuration
 *
 * Configuration for multi-layer residential proxy detection system.
 * Includes providers, thresholds, and special rules for Russian territories.
 *
 * @see App\Services\Security\ResidentialProxyDetectionService
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Residential Proxy Detection
    |--------------------------------------------------------------------------
    */
    'enabled' => env('RESIDENTIAL_PROXY_DETECTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | IP Intelligence Providers
    |--------------------------------------------------------------------------
    | Commercial and open-source providers for residential proxy detection.
    */
    'providers' => [
        // IPinfo Residential Proxy Detection
        'ipinfo' => [
            'enabled' => env('IPINFO_ENABLED', false),
            'api_key' => env('IPINFO_API_KEY'),
            'endpoint' => 'https://ipinfo.io',
            'timeout' => 5,
            'cache_ttl' => 3600, // 1 hour
        ],

        // MaxMind Proxy Detection + GeoIP2
        'maxmind' => [
            'enabled' => env('MAXMIND_ENABLED', false),
            'database_path' => env('MAXMIND_DATABASE_PATH', storage_path('app/maxmind/GeoLite2-Proxy.mmdb')),
            'license_key' => env('MAXMIND_LICENSE_KEY'),
            'account_id' => env('MAXMIND_ACCOUNT_ID'),
            'cache_ttl' => 3600,
        ],

        // FraudScore
        'fraudscore' => [
            'enabled' => env('FRAUDSCORE_ENABLED', false),
            'api_key' => env('FRAUDSCORE_API_KEY'),
            'endpoint' => 'https://api.fraudscore.com',
            'timeout' => 5,
        ],

        // GetIPIntel
        'getipintel' => [
            'enabled' => env('GETIPINTEL_ENABLED', false),
            'api_key' => env('GETIPINTEL_API_KEY'),
            'endpoint' => 'http://check.getipintel.net',
            'timeout' => 3,
            'threshold' => 0.8, // Probability threshold
        ],

        // AbuseIPDB
        'abuseipdb' => [
            'enabled' => env('ABUSEIPDB_ENABLED', false),
            'api_key' => env('ABUSEIPDB_API_KEY'),
            'endpoint' => 'https://api.abuseipdb.com/api/v2',
            'timeout' => 5,
            'max_age_days' => 90,
            'confidence_threshold' => 50,
        ],

        // IP2Proxy
        'ip2proxy' => [
            'enabled' => env('IP2PROXY_ENABLED', false),
            'database_path' => env('IP2PROXY_DATABASE_PATH', storage_path('app/ip2proxy/IP2PROXY.BIN')),
            'api_key' => env('IP2PROXY_API_KEY'),
            'cache_ttl' => 3600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Known Residential Proxy Ranges
    |--------------------------------------------------------------------------
    | CIDR ranges of known residential proxy providers.
    | Updated daily via FstecBduService integration.
    */
    'known_ranges' => [
        // Add known residential proxy ranges here
        // Example: '192.0.2.0/24',
        // Updated via scheduled job from threat intelligence feeds
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Thresholds
    |--------------------------------------------------------------------------
    | Thresholds for risk level calculation.
    */
    'thresholds' => [
        'low' => [
            'confidence_score' => 0,
            'behavioral_score' => 0.85,
        ],
        'medium' => [
            'confidence_score' => 30,
            'behavioral_score' => 0.75,
        ],
        'high' => [
            'confidence_score' => 60,
            'behavioral_score' => 0.60,
        ],
        'critical' => [
            'confidence_score' => 85,
            'behavioral_score' => 0.40,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Russian Territories Special Rules
    |--------------------------------------------------------------------------
    | Special handling for Russian territories (Crimea, Sevastopol, DPR, LPR, etc.)
    | Residential proxy from/to these territories = automatic HIGH/CRITICAL risk.
    */
    'russian_territories' => [
        'enabled' => true,
        'territories' => [
            'Crimea', 'Crimean Federal District',
            'Sevastopol',
            'Donetsk People\'s Republic', 'DPR',
            'Luhansk People\'s Republic', 'LPR',
            'Kherson Oblast', 'Kherson Region',
            'Zaporizhzhia Oblast', 'Zaporizhzhia Region',
        ],
        'country_codes' => ['RU', 'UA'], // Ukraine for pre-2022 territories
        'rules' => [
            'residential_proxy_from_foreign_country' => 'CRITICAL',
            'residential_proxy_to_foreign_country' => 'HIGH',
            'geo_mismatch' => 'CRITICAL',
            'any_proxy' => 'HIGH',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Behavioral Analysis Integration
    |--------------------------------------------------------------------------
    | Integration with BehavioralBiometricsService.
    */
    'behavioral' => [
        'enabled' => true,
        'min_samples_for_profile' => 5,
        'similarity_threshold' => 0.75,
        'anomaly_threshold' => 0.40,
        'weight_in_risk_calculation' => 0.3, // 30% weight in final risk score
    ],

    /*
    |--------------------------------------------------------------------------
    | Geo Consistency Checks
    |--------------------------------------------------------------------------
    | Geo-location consistency validation.
    */
    'geo' => [
        'enabled' => true,
        'check_previous_sessions' => true,
        'max_distance_km' => 500, // Max allowed distance between sessions
        'max_time_hours' => 24, // Time window for distance check
        'country_mismatch_risk' => 'HIGH',
    ],

    /*
    |--------------------------------------------------------------------------
    | Protection Measures
    |--------------------------------------------------------------------------
    | Gradient protection measures based on risk level.
    */
    'protection' => [
        'low' => [
            'log_only' => true,
            'rate_limit_multiplier' => 1.0,
        ],
        'medium' => [
            'managed_challenge' => true, // Turnstile
            'cooldown_hours' => 12,
            'rate_limit_multiplier' => 0.5,
            'notify_tenant_owners' => true,
        ],
        'high' => [
            'cooldown_hours' => 24,
            'invalidate_split_key' => true,
            'rate_limit_multiplier' => 0.2,
            'notify_tenant_owners' => true,
            'manual_review' => true,
        ],
        'critical' => [
            'cooldown_hours' => 72,
            'invalidate_split_key' => true,
            'logout_all_sessions' => true,
            'rate_limit_multiplier' => 0.1,
            'notify_tenant_owners' => true,
            'notify_super_admin' => true,
            'manual_review' => true,
        ],
        'permanent_block' => [
            'blacklist_ip' => true,
            'block_user' => true,
            'invalidate_split_key' => true,
            'logout_all_sessions' => true,
            'notify_super_admin' => true,
            'manual_review' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    | Redis cache for IP intelligence results.
    */
    'cache' => [
        'enabled' => true,
        'ttl_seconds' => 3600, // 1 hour
        'prefix' => 'residential_proxy:',
        'tags' => ['security', 'proxy_detection'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    | Security channel for logging proxy detection events.
    */
    'logging' => [
        'channel' => 'security',
        'log_clean_results' => false, // Only log detections, not clean IPs
        'log_metadata' => true,
        'include_request_headers' => false, // Don't log headers for PII protection
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Bot Management Integration
    |--------------------------------------------------------------------------
    | Integration with Cloudflare Bot Management for enhanced detection.
    */
    'cloudflare' => [
        'enabled' => env('CLOUDFLARE_BOT_MANAGEMENT_ENABLED', false),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'use_bot_score' => true,
        'bot_score_threshold' => 30, // Below 30 = likely bot
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Jobs
    |--------------------------------------------------------------------------
    | Scheduled jobs for updating threat intelligence.
    */
    'jobs' => [
        'update_ip_intelligence' => [
            'enabled' => true,
            'schedule' => '0 */6 * * *', // Every 6 hours
        ],
        'update_tor_exit_nodes' => [
            'enabled' => true,
            'schedule' => '0 */12 * * *', // Every 12 hours
        ],
        'cleanup_old_detections' => [
            'enabled' => true,
            'schedule' => '0 0 * * 0', // Weekly
            'retention_days' => 90,
        ],
        'expire_blacklist_entries' => [
            'enabled' => true,
            'schedule' => '0 * * * *', // Hourly
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Zero Tolerance Policy
    |--------------------------------------------------------------------------
    | Actions that trigger zero tolerance (permanent block).
    */
    'zero_tolerance' => [
        'enabled' => true,
        'triggers' => [
            'residential_proxy_mass_registration',
            'residential_proxy_hunting_pattern',
            'residential_proxy_insider_scraping',
            'residential_proxy_rf_territory_violation',
            'residential_proxy_behavioral_critical',
        ],
        'auto_block' => true,
        'require_manual_review' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Routes
    |--------------------------------------------------------------------------
    | Routes that require strict proxy detection.
    */
    'sensitive_routes' => [
        'api/auth/register',
        'api/auth/login',
        'api/auth/kyc/*',
        'api/kyb/*',
        'api/wallet/withdraw',
        'api/wallet/transfer',
        'api/users/*/bank-accounts',
        'api/tenants/*/staff',
    ],
];
