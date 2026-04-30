<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use App\Services\Infrastructure\DopplerService;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => 'sqlite',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Database Encryption Settings (CatVRF 2026 Security Fortress)
    |--------------------------------------------------------------------------
    |
    | Encryption at-rest settings for sensitive data.
    | - DB_ENCRYPTION_PEPPER: Additional secret for column-level encryption
    | - DB_ENCRYPTION_ENABLED: Enable/disable column-level encryption
    | - DB_BACKUP_ENCRYPTION: Enable backup encryption
    |
    */
    'encryption' => [
        'enabled' => DopplerService::get('DB_ENCRYPTION_ENABLED', true),
        'pepper' => DopplerService::get('DB_ENCRYPTION_PEPPER'),
        'backup_enabled' => DopplerService::get('DB_BACKUP_ENCRYPTION', true),
        'algorithm' => DopplerService::get('DB_ENCRYPTION_ALGORITHM', 'aes-256-gcm'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security Settings (CatVRF 2026 Security Fortress)
    |--------------------------------------------------------------------------
    |
    | Security hardening settings for database connections.
    | - DB_SSL_MODE: SSL/TLS mode for connections
    | - DB_STATEMENT_TIMEOUT: Query timeout in seconds
    | - DB_IDLE_IN_TRANSACTION_TIMEOUT: Idle transaction timeout
    |
    */
    'security' => [
        'ssl_mode' => DopplerService::get('DB_SSL_MODE', 'require'),
        'statement_timeout' => DopplerService::get('DB_STATEMENT_TIMEOUT', 30),
        'idle_in_transaction_timeout' => DopplerService::get('DB_IDLE_IN_TRANSACTION_TIMEOUT', 60),
        'log_slow_queries' => DopplerService::get('DB_LOG_SLOW_QUERIES', true),
        'slow_query_threshold_ms' => DopplerService::get('DB_SLOW_QUERY_THRESHOLD', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'central' => [
            'driver' => 'sqlite',
            'url' => DopplerService::get('DB_URL'),
            'database' => database_path('database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => DopplerService::get('DB_FOREIGN_KEYS', true),
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => DopplerService::get('DB_URL'),
            'database' => database_path('tenant.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => DopplerService::get('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'laravel',
            'username' => 'economplan2024@gmail.com',
            'password' => 'HVUYhvUY/2',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => DopplerService::get('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => DopplerService::get('DB_URL'),
            'host' => DopplerService::get('DB_HOST', '127.0.0.1'),
            'port' => DopplerService::get('DB_PORT', '3306'),
            'database' => DopplerService::get('DB_DATABASE', 'laravel'),
            'username' => DopplerService::get('DB_USERNAME', 'root'),
            'password' => DopplerService::get('DB_PASSWORD', ''),
            'unix_socket' => DopplerService::get('DB_SOCKET', ''),
            'charset' => DopplerService::get('DB_CHARSET', 'utf8mb4'),
            'collation' => DopplerService::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => DopplerService::get('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => DopplerService::get('DB_URL'),
            'host' => DopplerService::get('DB_HOST', '127.0.0.1'),
            'port' => DopplerService::get('DB_PORT', '5432'),
            'database' => DopplerService::get('DB_DATABASE', 'laravel'),
            'username' => DopplerService::get('DB_USERNAME', 'root'),
            'password' => DopplerService::get('DB_PASSWORD', ''),
            'charset' => DopplerService::get('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => DopplerService::get('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => DopplerService::get('DB_URL'),
            'host' => DopplerService::get('DB_HOST', 'localhost'),
            'port' => DopplerService::get('DB_PORT', '1433'),
            'database' => DopplerService::get('DB_DATABASE', 'laravel'),
            'username' => DopplerService::get('DB_USERNAME', 'root'),
            'password' => DopplerService::get('DB_PASSWORD', ''),
            'charset' => DopplerService::get('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => App\Services\Infrastructure\DopplerService::get('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => App\Services\Infrastructure\DopplerService::get('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

        'clickhouse' => [
            'driver' => 'clickhouse',
            'host' => DopplerService::get('CLICKHOUSE_HOST', '127.0.0.1'),
            'port' => DopplerService::get('CLICKHOUSE_PORT', '8123'),
            'database' => DopplerService::get('CLICKHOUSE_DATABASE', 'catvrf_bigdata'),
            'username' => DopplerService::get('CLICKHOUSE_USERNAME', 'default'),
            'password' => DopplerService::get('CLICKHOUSE_PASSWORD', ''),
            'options' => [
                'timeout' => DopplerService::get('CLICKHOUSE_TIMEOUT', 30),
                'connect_timeout' => DopplerService::get('CLICKHOUSE_CONNECT_TIMEOUT', 10),
            ],
            'prefix' => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => DopplerService::get('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => DopplerService::get('REDIS_CLUSTER_MODE', 'redis'),
            'prefix' => DopplerService::get('REDIS_PREFIX', Str::slug((string) DopplerService::get('APP_NAME', 'laravel')).'-database-'),
            'persistent' => DopplerService::get('REDIS_PERSISTENT', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Redis Cluster Configuration (Production 2026)
        |--------------------------------------------------------------------------
        |
        | For production with 28 verticals, use Redis Cluster for horizontal scaling.
        | Set REDIS_CLUSTER_ENABLED=true in .env to enable cluster mode.
        |
        */
        'cluster' => [
            'enabled' => DopplerService::get('REDIS_CLUSTER_ENABLED', false),
            'url' => DopplerService::get('REDIS_CLUSTER_URL'),
            'options' => [
                'cluster' => 'redis',
                'prefix' => DopplerService::get('REDIS_PREFIX', 'catvrf:'),
            ],
            'parameters' => [
                'password' => DopplerService::get('REDIS_PASSWORD'),
                'database' => DopplerService::get('REDIS_DB', '0'),
            ],
        ],

        'default' => [
            'url' => DopplerService::get('REDIS_URL'),
            'host' => DopplerService::get('REDIS_HOST', '127.0.0.1'),
            'username' => DopplerService::get('REDIS_USERNAME'),
            'password' => DopplerService::get('REDIS_PASSWORD'),
            'port' => DopplerService::get('REDIS_PORT', '6379'),
            'database' => DopplerService::get('REDIS_DB', '0'),
            'max_retries' => DopplerService::get('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => DopplerService::get('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => DopplerService::get('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => DopplerService::get('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => DopplerService::get('REDIS_URL'),
            'host' => DopplerService::get('REDIS_HOST', '127.0.0.1'),
            'username' => DopplerService::get('REDIS_USERNAME'),
            'password' => DopplerService::get('REDIS_PASSWORD'),
            'port' => DopplerService::get('REDIS_PORT', '6379'),
            'database' => DopplerService::get('REDIS_CACHE_DB', '1'),
            'max_retries' => DopplerService::get('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => DopplerService::get('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => DopplerService::get('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => DopplerService::get('REDIS_BACKOFF_CAP', 1000),
        ],

        /*
        |--------------------------------------------------------------------------
        | WebSocket Scaling - Reverb Connection
        |--------------------------------------------------------------------------
        |
        | Dedicated Redis connection for Laravel Reverb horizontal scaling.
        | Optimized for low-latency pub/sub operations with 28 verticals.
        | Use 'cluster' connection when REDIS_CLUSTER_ENABLED=true.
        |
        */
        'reverb' => [
            'url' => DopplerService::get('REDIS_URL'),
            'host' => DopplerService::get('REDIS_HOST', '127.0.0.1'),
            'username' => DopplerService::get('REDIS_USERNAME'),
            'password' => DopplerService::get('REDIS_PASSWORD'),
            'port' => DopplerService::get('REDIS_PORT', '6379'),
            'database' => DopplerService::get('REDIS_REVERB_DB', '2'),
            'max_retries' => DopplerService::get('REDIS_MAX_RETRIES', 3),
            'read_timeout' => DopplerService::get('REDIS_READ_TIMEOUT', 2.0),
            'write_timeout' => DopplerService::get('REDIS_WRITE_TIMEOUT', 2.0),
            'persistent' => DopplerService::get('REDIS_PERSISTENT', true),
            'backoff_algorithm' => DopplerService::get('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => DopplerService::get('REDIS_BACKOFF_BASE', 50),
            'backoff_cap' => DopplerService::get('REDIS_BACKOFF_CAP', 500),
        ],

    ],

];
