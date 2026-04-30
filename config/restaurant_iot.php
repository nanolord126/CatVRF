<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | MQTT Configuration
    |--------------------------------------------------------------------------
    |
    | MQTT broker settings for IoT device communication
    |
    */
    'mqtt' => [
        'host' => env('MQTT_HOST', '127.0.0.1'),
        'port' => (int) env('MQTT_PORT', 1883),
        'username' => env('MQTT_USERNAME'),
        'password' => env('MQTT_PASSWORD'),
        'client_id' => env('MQTT_CLIENT_ID', 'catvrf_iot_hub_' . uniqid()),
        'use_tls' => env('MQTT_USE_TLS', false),
        'connect_timeout' => (int) env('MQTT_CONNECT_TIMEOUT', 5),
        'socket_timeout' => (int) env('MQTT_SOCKET_TIMEOUT', 5),
        'resend_timeout' => (int) env('MQTT_RESEND_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Configuration
    |--------------------------------------------------------------------------
    |
    | WebSocket settings for real-time IoT communication
    |
    */
    'websocket' => [
        'enabled' => env('IOT_WEBSOCKET_ENABLED', true),
        'channel_prefix' => env('IOT_WS_CHANNEL_PREFIX', 'iot.device'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modbus Configuration
    |--------------------------------------------------------------------------
    |
    | Modbus TCP/RTU settings for industrial equipment
    |
    */
    'modbus' => [
        'default_timeout' => (float) env('MODBUS_TIMEOUT', 5.0),
        'default_port' => (int) env('MODBUS_DEFAULT_PORT', 502),
        'retry_attempts' => (int) env('MODBUS_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telemetry Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for telemetry data collection and storage
    |
    */
    'telemetry' => [
        'retention_days' => (int) env('IOT_TELEMETRY_RETENTION_DAYS', 90),
        'batch_size' => (int) env('IOT_TELEMETRY_BATCH_SIZE', 100),
        'clickhouse_enabled' => env('IOT_CLICKHOUSE_ENABLED', false),
        'clickhouse_database' => env('IOT_CLICKHOUSE_DATABASE', 'catvrf_iot'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Alert thresholds and notification settings
    |
    */
    'alerts' => [
        'temperature' => [
            'warning_threshold' => (float) env('IOT_TEMP_WARNING', 8.0),
            'critical_threshold' => (float) env('IOT_TEMP_CRITICAL', 15.0),
        ],
        'weight' => [
            'min_threshold' => (float) env('IOT_WEIGHT_MIN', 0.01),
        ],
        'offline_threshold_minutes' => (int) env('IOT_OFFLINE_THRESHOLD', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for IoT devices
    |
    */
    'devices' => [
        'auto_register' => env('IOT_AUTO_REGISTER', false),
        'require_approval' => env('IOT_REQUIRE_APPROVAL', true),
        'heartbeat_interval_seconds' => (int) env('IOT_HEARTBEAT_INTERVAL', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | IoT security settings including TLS, certificates, rate limiting,
    | anomaly detection, and quarantine policies
    |
    */
    'security' => [
        // TLS/mTLS Configuration
        'tls' => [
            'enabled' => env('IOT_TLS_ENABLED', true),
            'min_version' => env('IOT_TLS_VERSION', 'TLSv1.3'),
            'verify_peer' => env('IOT_TLS_VERIFY_PEER', true),
            'verify_depth' => (int) env('IOT_TLS_VERIFY_DEPTH', 5),
            'mtls_enabled' => env('IOT_MTLS_ENABLED', true),
            'ca_file' => env('IOT_TLS_CA_FILE', storage_path('iot/ca.crt')),
            'cert_file' => env('IOT_TLS_CERT_FILE', storage_path('iot/client.crt')),
            'key_file' => env('IOT_TLS_KEY_FILE', storage_path('iot/client.key')),
        ],

        // Certificate Management
        'certificates' => [
            'rotation_days' => (int) env('IOT_CERT_ROTATION_DAYS', 30),
            'warning_days' => (int) env('IOT_CERT_WARNING_DAYS', 7),
            'key_algorithm' => env('IOT_CERT_KEY_ALGORITHM', 'RSA'),
            'key_bits' => (int) env('IOT_CERT_KEY_BITS', 4096),
            'signature_algorithm' => env('IOT_CERT_SIGNATURE_ALGORITHM', 'SHA256'),
        ],

        // Rate Limiting
        'rate_limiting' => [
            'enabled' => env('IOT_RATE_LIMIT_ENABLED', true),
            'default_per_minute' => (int) env('IOT_RATE_LIMIT_DEFAULT', 60),
            'burst_per_minute' => (int) env('IOT_RATE_LIMIT_BURST', 100),
            'window_seconds' => (int) env('IOT_RATE_LIMIT_WINDOW', 60),
        ],

        // Anomaly Detection
        'anomaly_detection' => [
            'enabled' => env('IOT_ANOMALY_DETECTION_ENABLED', true),
            'deviation_threshold' => (float) env('IOT_ANOMALY_DEVIATION_THRESHOLD', 0.5), // 50%
            'history_size' => (int) env('IOT_ANOMALY_HISTORY_SIZE', 10),
            'quarantine_threshold' => (int) env('IOT_ANOMALY_QUARANTINE_THRESHOLD', 3), // anomalies before quarantine
            'time_window_minutes' => (int) env('IOT_ANOMALY_TIME_WINDOW', 10),
        ],

        // Replay Protection
        'replay_protection' => [
            'enabled' => env('IOT_REPLAY_PROTECTION_ENABLED', true),
            'nonce_ttl_seconds' => (int) env('IOT_NONCE_TTL', 300), // 5 minutes
        ],

        // Quarantine Policy
        'quarantine' => [
            'auto_quarantine_enabled' => env('IOT_AUTO_QUARANTINE_ENABLED', true),
            'auto_quarantine_on' => [
                'mitm_attempt' => true,
                'replay_attack' => true,
                'signature_failed' => true,
                'certificate_revoked' => true,
                'device_spoofing' => true,
                'unauthorized_access' => true,
            ],
            'notification_enabled' => env('IOT_QUARANTINE_NOTIFY_ENABLED', true),
            'notification_channels' => explode(',', env('IOT_QUARANTINE_NOTIFY_CHANNELS', 'email,slack')),
        ],

        // Security Events
        'security_events' => [
            'retention_days' => (int) env('IOT_SECURITY_EVENTS_RETENTION_DAYS', 365),
            'log_to_clickhouse' => env('IOT_SECURITY_EVENTS_CLICKHOUSE', false),
            'alert_on_critical' => env('IOT_SECURITY_ALERT_CRITICAL', true),
            'alert_on_emergency' => env('IOT_SECURITY_ALERT_EMERGENCY', true),
        ],

        // Network Isolation
        'network' => [
            'tenant_isolation_enabled' => env('IOT_TENANT_ISOLATION_ENABLED', true),
            'allowed_ip_ranges' => explode(',', env('IOT_ALLOWED_IP_RANGES', '')),
            'blocked_ip_ranges' => explode(',', env('IOT_BLOCKED_IP_RANGES', '')),
        ],

        // Payload Encryption
        'encryption' => [
            'enabled' => env('IOT_PAYLOAD_ENCRYPTION_ENABLED', false), // Optional: encrypt payload on top of TLS
            'algorithm' => env('IOT_ENCRYPTION_ALGORITHM', 'AES-256-GCM'),
            'key_rotation_days' => (int) env('IOT_ENCRYPTION_KEY_ROTATION_DAYS', 90),
        ],
    ],
];
