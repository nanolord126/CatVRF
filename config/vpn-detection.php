<?php

declare(strict_types=1);

use App\Enums\VpnRiskLevel;

return [
    /*
    |--------------------------------------------------------------------------
    | VPN Detection Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-layered VPN/Proxy/Tor detection system.
    | Supports multiple IP intelligence providers and custom rules.
    |
    */

    'enabled' => env('VPN_DETECTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | IP Intelligence Providers
    |--------------------------------------------------------------------------
    |
    | Configure external IP intelligence services for VPN detection.
    | Multiple sources provide better accuracy through consensus.
    |
    */
    'providers' => [
        // MaxMind GeoIP2 Database
        'maxmind' => [
            'enabled' => env('MAXMIND_ENABLED', false),
            'database_path' => env('MAXMIND_DATABASE_PATH', database_path('geoip/GeoLite2-City.mmdb')),
            'license_key' => env('MAXMIND_LICENSE_KEY'),
            'account_id' => env('MAXMIND_ACCOUNT_ID'),
        ],

        // IP2Location Database
        'ip2location' => [
            'enabled' => env('IP2LOCATION_ENABLED', false),
            'database_path' => env('IP2LOCATION_DATABASE_PATH', database_path('geoip/IP2LOCATION-LITE-DB11.BIN')),
            'api_key' => env('IP2LOCATION_API_KEY'),
        ],

        // AbuseIPDB API
        'abuseipdb' => [
            'enabled' => env('ABUSEIPDB_ENABLED', false),
            'api_key' => env('ABUSEIPDB_API_KEY'),
            'api_url' => 'https://api.abuseipdb.com/api/v2/check',
            'max_age_days' => 90,
            'confidence_threshold' => 50,
        ],

        // GetIPIntel API
        'getipintel' => [
            'enabled' => env('GETIPINTEL_ENABLED', false),
            'api_key' => env('GETIPINTEL_API_KEY'),
            'api_url' => 'http://check.getipintel.net/check.php',
            'probability_threshold' => 0.8,
        ],

        // Tor Exit Node List
        'tor' => [
            'enabled' => env('TOR_DETECTION_ENABLED', true),
            'list_url' => 'https://check.torproject.org/torbulkexitlist',
            'cache_ttl' => 3600, // 1 hour
        ],

        // Residential Proxy Detection
        'residential_proxy' => [
            'enabled' => env('RESIDENTIAL_PROXY_DETECTION_ENABLED', true),
            'known_ranges' => [
                // Add known residential proxy CIDR ranges
                // '103.21.244.0/22',
                // '103.22.200.0/22',
            ],
            'latency_threshold_ms' => 500,
        ],

        // Known VPN ASNs
        'known_vpn_asns' => [
            // Mullvad VPN
            52077,
            // NordVPN
            39631,
            // ExpressVPN
            54766,
            // CyberGhost
            59655,
            // Surfshark
            57821,
            // ProtonVPN
            204467,
            // Windscribe
            32787,
            // IPVanish
            36351,
            // Private Internet Access
            36352,
            // VyprVPN
            14061,
            // Hide.me
            199524,
            // AWS Lightsail (often used for VPNs)
            14618,
            // DigitalOcean
            14061,
            // Hetzner
            24940,
            // Oracle Cloud
            31898,
            // Google Cloud
            15169,
            // Microsoft Azure
            8075,
        ],

        // Datacenter ASNs
        'datacenter_asns' => [
            14618, // AWS
            14061, // DigitalOcean
            24940, // Hetzner
            31898, // Oracle Cloud
            15169, // Google Cloud
            8075, // Microsoft Azure
            16509, // Amazon
            19551, // SoftLayer
            16276, // OVH
            46606, 46609, // UNILOGICNET
        ],

        // Datacenter ISP patterns
        'datacenter_isps' => [
            'digitalocean',
            'amazon',
            'google cloud',
            'microsoft azure',
            'hetzner',
            'ovh',
            'linode',
            'vultr',
            'aws',
            'alibaba cloud',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Corporate VPN Whitelist
    |--------------------------------------------------------------------------
    |
    | Whitelist for known corporate VPNs to avoid false positives.
    | These are treated as LOW risk.
    |
    */
    'corporate_vpn' => [
        'enabled' => true,
        'whitelist' => [
            'ips' => [
                // Add corporate VPN IP ranges in CIDR format
                // '10.0.0.0/8',
                // '172.16.0.0/12',
                // '192.168.0.0/16',
            ],
            'asns' => [
                // Add corporate VPN ASNs
                // 12345,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Level Thresholds
    |--------------------------------------------------------------------------
    |
    | Configure risk level thresholds based on detection factors.
    |
    */
    'risk_thresholds' => [
        // Minimum sources required for detection
        'min_detection_sources' => 1,

        // Risk level escalation rules
        'escalation_rules' => [
            // Tor always HIGH
            'tor_to_high' => true,

            // Datacenter + behavioral anomaly = HIGH
            'datacenter_anomaly_to_high' => true,

            // Residential proxy = HIGH
            'residential_proxy_to_high' => true,

            // Commercial VPN without flags = MEDIUM
            'commercial_vpn_to_medium' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Protection Measures
    |--------------------------------------------------------------------------
    |
    | Configure protection measures based on risk levels.
    |
    */
    'protection' => [
        // Cooldown durations in hours per risk level
        'cooldown_hours' => [
            VpnRiskLevel::LOW->value => 0,
            VpnRiskLevel::MEDIUM->value => 12,
            VpnRiskLevel::HIGH->value => 24,
            VpnRiskLevel::CRITICAL->value => 72,
        ],

        // Split key invalidation
        'invalidate_split_key' => [
            VpnRiskLevel::HIGH->value => true,
            VpnRiskLevel::CRITICAL->value => true,
        ],

        // Session termination
        'terminate_sessions' => [
            VpnRiskLevel::CRITICAL->value => true,
        ],

        // Require fresh Passkey
        'require_fresh_passkey' => [
            VpnRiskLevel::MEDIUM->value => true,
            VpnRiskLevel::HIGH->value => true,
            VpnRiskLevel::CRITICAL->value => true,
        ],

        // Require manual review
        'require_manual_review' => [
            VpnRiskLevel::HIGH->value => true,
            VpnRiskLevel::CRITICAL->value => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notification rules for VPN detection events.
    |
    */
    'notifications' => [
        'enabled' => true,

        // Notify user
        'notify_user' => [
            VpnRiskLevel::MEDIUM->value => true,
            VpnRiskLevel::HIGH->value => true,
            VpnRiskLevel::CRITICAL->value => true,
        ],

        // Notify tenant owners
        'notify_tenant_owners' => [
            VpnRiskLevel::MEDIUM->value => true,
            VpnRiskLevel::HIGH->value => true,
            VpnRiskLevel::CRITICAL->value => true,
        ],

        // Notify all stakeholders (owners + investors)
        'notify_all_stakeholders' => [
            VpnRiskLevel::HIGH->value => true,
            VpnRiskLevel::CRITICAL->value => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Russian Territories Special Rules
    |--------------------------------------------------------------------------
    |
    | Special handling for Russian territories (Crimea, Sevastopol, DPR, LPR, etc.)
    | VPN from these regions showing different country = automatic HIGH risk.
    |
    */
    'russian_territories' => [
        'enabled' => true,

        'territories' => [
            'Crimea',
            'Crimean Federal District',
            'Sevastopol',
            'Donetsk People\'s Republic',
            'DPR',
            'Luhansk People\'s Republic',
            'LPR',
            'Kherson Oblast',
            'Kherson Region',
            'Zaporizhzhia Oblast',
            'Zaporizhzhia Region',
        ],

        // Auto-escalate to HIGH if geo mismatch detected
        'geo_mismatch_to_high' => true,

        // Require manual review for registration/KYB from these regions with VPN
        'require_manual_review_for_registration' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Routes
    |--------------------------------------------------------------------------
    |
    | Routes that trigger higher risk assessment when accessed via VPN.
    |
    */
    'sensitive_routes' => [
        'api/auth/register',
        'api/auth/verify-business',
        'api/bank-accounts/*',
        'api/withdrawals/*',
        'api/transfers/*',
        'api/settings/bank-details',
        'api/settings/withdrawal-methods',
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache VPN detection results to improve performance and reduce API calls.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl_seconds' => 3600, // 1 hour
        'prefix' => 'vpn_detection:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for VPN detection events.
    |
    */
    'logging' => [
        'channel' => 'security',
        'log_clean_results' => false, // Only log VPN detections, not clean results
        'log_detailed_metadata' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limit external API calls to avoid hitting quotas.
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute' => 60,
        'cache_ttl_seconds' => 300, // 5 minutes
    ],
];
