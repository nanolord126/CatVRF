<?php

declare(strict_types=1);

/**
 * Bot Protection Configuration
 * 
 * Multi-layered bot detection and protection system for CatVRF.
 * 
 * Layers:
 * - Layer 0: Edge Protection (Cloudflare Bot Management)
 * - Layer 1: Laravel Middleware + Honeypots
 * - Layer 2: Behavioral Biometrics + AI Detection
 * - Layer 3: FraudControl + VpnDetection + InsiderThreat
 * 
 * @see https://github.com/nanolord126/CatVRF
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Bot Protection
    |--------------------------------------------------------------------------
    |
    | Global enable/disable for bot protection system.
    | Set to false in development/testing environments.
    |
    */
    'enabled' => env('BOT_PROTECTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Detection Layers
    |--------------------------------------------------------------------------
    |
    | Configure which detection layers are active.
    |
    */
    'layers' => [
        'edge_protection' => env('BOT_EDGE_PROTECTION_ENABLED', true),
        'middleware_checks' => env('BOT_MIDDLEWARE_CHECKS_ENABLED', true),
        'behavioral_analysis' => env('BOT_BEHAVIORAL_ANALYSIS_ENABLED', true),
        'vpn_detection' => env('BOT_VPN_DETECTION_ENABLED', true),
        'fraud_integration' => env('BOT_FRAUD_INTEGRATION_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Thresholds
    |--------------------------------------------------------------------------
    |
    | Confidence thresholds for each risk level (0.0 - 1.0).
    |
    */
    'thresholds' => [
        'medium' => (float) env('BOT_RISK_THRESHOLD_MEDIUM', 0.4),
        'high' => (float) env('BOT_RISK_THRESHOLD_HIGH', 0.65),
        'critical' => (float) env('BOT_RISK_THRESHOLD_CRITICAL', 0.85),
    ],

    /*
    |--------------------------------------------------------------------------
    | Known Good Bots (Whitelist)
    |--------------------------------------------------------------------------
    |
    | User agents of known legitimate bots/crawlers.
    | These will be marked as LOW risk and logged only.
    |
    */
    'whitelist' => [
        'user_agents' => [
            'Googlebot',
            'Bingbot',
            'Slackbot',
            'Twitterbot',
            'FacebookExternalHit',
            'LinkedInBot',
            'WhatsApp',
            'Applebot',
            'YandexBot',
            'DuckDuckBot',
            'Baiduspider',
        ],
        'ip_ranges' => [
            // Google
            '66.249.64.0/19',
            '66.249.88.0/24',
            // Microsoft/Bing
            '40.77.167.0/24',
            '13.66.139.0/24',
            // Add more ranges as needed
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Known Bad Bots (Blacklist)
    |--------------------------------------------------------------------------
    |
    | User agents and patterns of known malicious bots.
    | These will be immediately marked as CRITICAL risk.
    |
    */
    'blacklist' => [
        'user_agents' => [
            'scrapy',
            'curl',
            'wget',
            'python-requests',
            'libwww-perl',
            'java',
            'jakarta',
            'httpclient',
            'nikto',
            'sqlmap',
            'nmap',
            'masscan',
            'zgrab',
            'go-http-client',
            'postman',
            'insomnia',
        ],
        'patterns' => [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/harvest/i',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Headless Browser Detection
    |--------------------------------------------------------------------------
    |
    | Patterns to detect headless browsers (Chrome, Firefox, etc.).
    |
    */
    'headless_detection' => [
        'enabled' => env('BOT_HEADLESS_DETECTION_ENABLED', true),
        'user_agent_patterns' => [
            'HeadlessChrome',
            'PhantomJS',
            'SlimerJS',
            'Selenium',
            'WebDriver',
        ],
        'navigator_patterns' => [
            'navigator.webdriver',
            'navigator.plugins.length === 0',
            'navigator.languages.length === 0',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Behavioral Anomaly Detection
    |--------------------------------------------------------------------------
    |
    | Thresholds for detecting bot-like behavior patterns.
    |
    */
    'behavioral_detection' => [
        'enabled' => env('BOT_BEHAVIORAL_DETECTION_ENABLED', true),
        'typing_speed' => [
            // WPM (words per minute) thresholds
            'too_fast_min' => env('BOT_TYPING_TOO_FAST_MIN', 150), // >150 WPM is suspicious
            'too_constant_variance' => env('BOT_TYPING_CONSTANT_VARIANCE', 0.1), // <10% variance is suspicious
        ],
        'mouse_movement' => [
            'too_straight_lines' => env('BOT_MOUSE_STRAIGHT_LINES', 0.95), // >95% straight lines is suspicious
            'no_random_jitter' => env('BOT_MOUSE_NO_JITTER', true), // Perfect movements are suspicious
        ],
        'session_patterns' => [
            'no_idle_time' => env('BOT_NO_IDLE_TIME', true), // Continuous activity is suspicious
            'page_load_time_min_ms' => env('BOT_PAGE_LOAD_MIN_MS', 100), // <100ms is suspicious
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Honeypot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for honeypot fields in forms.
    |
    */
    'honeypot' => [
        'enabled' => env('BOT_HONEYPOT_ENABLED', true),
        'field_names' => [
            'website_url',
            'fax_number',
            'middle_name',
            'company_address',
        ],
        'css_class' => 'bot-protection-honeypot',
        'hidden_style' => 'display:none;visibility:hidden;height:0;width:0;',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for suspicious IP addresses.
    |
    */
    'rate_limiting' => [
        'enabled' => env('BOT_RATE_LIMITING_ENABLED', true),
        'by_ip' => [
            'medium_risk' => [
                'max_attempts' => env('BOT_RATE_MEDIUM_MAX', 30),
                'decay_minutes' => env('BOT_RATE_MEDIUM_DECAY', 5),
            ],
            'high_risk' => [
                'max_attempts' => env('BOT_RATE_HIGH_MAX', 5),
                'decay_minutes' => env('BOT_RATE_HIGH_DECAY', 5),
            ],
        ],
        'by_user' => [
            'registration_attempts' => [
                'max_attempts' => env('BOT_REGISTRATION_MAX', 3),
                'decay_minutes' => env('BOT_REGISTRATION_DECAY', 60),
            ],
            'client_search_attempts' => [
                'max_attempts' => env('BOT_CLIENT_SEARCH_MAX', 20),
                'decay_minutes' => env('BOT_CLIENT_SEARCH_DECAY', 5),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Russian Territories Special Rules
    |--------------------------------------------------------------------------
    |
    | Special protection rules for Russian territories.
    |
    */
    'russian_territories' => [
        'enabled' => env('BOT_RUSSIAN_TERRITORIES_ENABLED', true),
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
        'vpn_auto_high_risk' => env('BOT_RUSSIAN_VPN_HIGH_RISK', true),
        'scraping_critical_risk' => env('BOT_RUSSIAN_SCRAPING_CRITICAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical Routes
    |--------------------------------------------------------------------------
    |
    | Routes that require enhanced bot protection.
    |
    */
    'critical_routes' => [
        'registration' => [
            'api/*/register',
            'api/*/signup',
            'auth/register',
        ],
        'kyb' => [
            'api/*/kyb/*',
            'filament/*/resources/business-groups/*/edit',
        ],
        'client_search' => [
            'api/*/clients/search',
            'api/*/customers/search',
            'filament/*/resources/client/*/list',
        ],
        'data_export' => [
            'api/*/export/*',
            'filament/*/resources/*/export',
        ],
        'admin' => [
            'filament/*',
            'admin/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Protection Measures
    |--------------------------------------------------------------------------
    |
    | Actions to take based on risk level.
    |
    */
    'protection' => [
        'medium' => [
            'challenge' => env('BOT_MEDIUM_CHALLENGE', 'turnstile'), // 'turnstile' or 'captcha'
            'rate_limit' => true,
            'log_only' => false,
        ],
        'high' => [
            'block' => true,
            'cooldown_hours' => env('BOT_HIGH_COOLDOWN_HOURS', 24),
            'invalidate_split_key' => env('BOT_HIGH_INVALIDATE_SPLIT_KEY', true),
            'notify_owners' => true,
            'notify_super_admins' => false,
        ],
        'critical' => [
            'block' => true,
            'cooldown_hours' => env('BOT_CRITICAL_COOLDOWN_HOURS', 168), // 7 days
            'invalidate_split_key' => true,
            'notify_owners' => true,
            'notify_super_admins' => true,
            'permanent_ip_block' => env('BOT_CRITICAL_PERMANENT_BLOCK', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Turnstile Configuration
    |--------------------------------------------------------------------------
    |
    | Cloudflare Turnstile CAPTCHA configuration.
    |
    */
    'turnstile' => [
        'enabled' => env('BOT_TURNSTILE_ENABLED', true),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for detection results.
    |
    */
    'cache' => [
        'enabled' => env('BOT_CACHE_ENABLED', true),
        'ttl_seconds' => env('BOT_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'bot_detection:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Logging settings for bot detection events.
    |
    */
    'logging' => [
        'channel' => env('BOT_LOG_CHANNEL', 'security'),
        'log_all_detections' => env('BOT_LOG_ALL', true),
        'log_only_risky' => env('BOT_LOG_ONLY_RISKY', false),
        'log_to_clickhouse' => env('BOT_LOG_CLICKHOUSE', true),
        'anonymize_ip' => env('BOT_ANONYMIZE_IP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Notification settings for bot detection alerts.
    |
    */
    'notifications' => [
        'enabled' => env('BOT_NOTIFICATIONS_ENABLED', true),
        'channels' => [
            'email' => env('BOT_NOTIFY_EMAIL', true),
            'slack' => env('BOT_NOTIFY_SLACK', false),
            'telegram' => env('BOT_NOTIFY_TELEGRAM', false),
        ],
        'high_risk_cooldown_minutes' => env('BOT_HIGH_NOTIFY_COOLDOWN', 30),
        'critical_risk_cooldown_minutes' => env('BOT_CRITICAL_NOTIFY_COOLDOWN', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Services
    |--------------------------------------------------------------------------
    |
    | Configuration for integrated security services.
    |
    */
    'integrations' => [
        'behavioral_biometrics' => [
            'enabled' => env('BOT_INTEGRATION_BEHAVIORAL', true),
            'weight' => env('BOT_BEHAVIORAL_WEIGHT', 0.4), // 40% weight in risk calculation
        ],
        'vpn_detection' => [
            'enabled' => env('BOT_INTEGRATION_VPN', true),
            'weight' => env('BOT_VPN_WEIGHT', 0.3), // 30% weight in risk calculation
        ],
        'fraud_control' => [
            'enabled' => env('BOT_INTEGRATION_FRAUD', true),
            'weight' => env('BOT_FRAUD_WEIGHT', 0.3), // 30% weight in risk calculation
        ],
    ],
];
