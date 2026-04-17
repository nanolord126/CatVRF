<?php

use Illuminate\Support\Str;

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

    'connections' => [

        'central' => [
            'driver' => 'sqlite',
            'url' => App\Services\Infrastructure\DopplerService::get('DB_URL'),
            'database' => database_path('database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => App\Services\Infrastructure\DopplerService::get('DB_FOREIGN_KEYS', true),
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => App\Services\Infrastructure\DopplerService::get('DB_URL'),
            'database' => database_path('tenant.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => App\Services\Infrastructure\DopplerService::get('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => App\Services\Infrastructure\DopplerService::get('DB_URL'),
            'host' => App\Services\Infrastructure\DopplerService::get('DB_HOST', '127.0.0.1'),
            'port' => App\Services\Infrastructure\DopplerService::get('DB_PORT', '3306'),
            'database' => App\Services\Infrastructure\DopplerService::get('DB_DATABASE', 'laravel'),
            'username' => App\Services\Infrastructure\DopplerService::get('DB_USERNAME', 'root'),
            'password' => App\Services\Infrastructure\DopplerService::get('DB_PASSWORD', ''),
            'unix_socket' => App\Services\Infrastructure\DopplerService::get('DB_SOCKET', ''),
            'charset' => App\Services\Infrastructure\DopplerService::get('DB_CHARSET', 'utf8mb4'),
            'collation' => App\Services\Infrastructure\DopplerService::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA) => App\Services\Infrastructure\DopplerService::get('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => App\Services\Infrastructure\DopplerService::get('DB_URL'),
            'host' => App\Services\Infrastructure\DopplerService::get('DB_HOST', '127.0.0.1'),
            'port' => App\Services\Infrastructure\DopplerService::get('DB_PORT', '3306'),
            'database' => App\Services\Infrastructure\DopplerService::get('DB_DATABASE', 'laravel'),
            'username' => App\Services\Infrastructure\DopplerService::get('DB_USERNAME', 'root'),
            'password' => App\Services\Infrastructure\DopplerService::get('DB_PASSWORD', ''),
            'unix_socket' => App\Services\Infrastructure\DopplerService::get('DB_SOCKET', ''),
            'charset' => App\Services\Infrastructure\DopplerService::get('DB_CHARSET', 'utf8mb4'),
            'collation' => App\Services\Infrastructure\DopplerService::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA) => App\Services\Infrastructure\DopplerService::get('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => App\Services\Infrastructure\DopplerService::get('DB_URL'),
            'host' => App\Services\Infrastructure\DopplerService::get('DB_HOST', '127.0.0.1'),
            'port' => App\Services\Infrastructure\DopplerService::get('DB_PORT', '5432'),
            'database' => App\Services\Infrastructure\DopplerService::get('DB_DATABASE', 'laravel'),
            'username' => App\Services\Infrastructure\DopplerService::get('DB_USERNAME', 'root'),
            'password' => App\Services\Infrastructure\DopplerService::get('DB_PASSWORD', ''),
            'charset' => App\Services\Infrastructure\DopplerService::get('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => App\Services\Infrastructure\DopplerService::get('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => App\Services\Infrastructure\DopplerService::get('DB_URL'),
            'host' => App\Services\Infrastructure\DopplerService::get('DB_HOST', 'localhost'),
            'port' => App\Services\Infrastructure\DopplerService::get('DB_PORT', '1433'),
            'database' => App\Services\Infrastructure\DopplerService::get('DB_DATABASE', 'laravel'),
            'username' => App\Services\Infrastructure\DopplerService::get('DB_USERNAME', 'root'),
            'password' => App\Services\Infrastructure\DopplerService::get('DB_PASSWORD', ''),
            'charset' => App\Services\Infrastructure\DopplerService::get('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => App\Services\Infrastructure\DopplerService::get('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => App\Services\Infrastructure\DopplerService::get('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

        'clickhouse' => [
            'driver' => 'clickhouse',
            'host' => App\Services\Infrastructure\DopplerService::get('CLICKHOUSE_HOST', '127.0.0.1'),
            'port' => App\Services\Infrastructure\DopplerService::get('CLICKHOUSE_PORT', '8123'),
            'database' => App\Services\Infrastructure\DopplerService::get('CLICKHOUSE_DATABASE', 'catvrf_analytics'),
            'username' => App\Services\Infrastructure\DopplerService::get('CLICKHOUSE_USERNAME', 'default'),
            'password' => App\Services\Infrastructure\DopplerService::get('CLICKHOUSE_PASSWORD', ''),
            'options' => [
                'timeout' => App\Services\Infrastructure\DopplerService::get('CLICKHOUSE_TIMEOUT', 30),
                'connect_timeout' => App\Services\Infrastructure\DopplerService::get('CLICKHOUSE_CONNECT_TIMEOUT', 10),
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

        'client' => App\Services\Infrastructure\DopplerService::get('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => App\Services\Infrastructure\DopplerService::get('REDIS_CLUSTER', 'redis'),
            'prefix' => App\Services\Infrastructure\DopplerService::get('REDIS_PREFIX', Str::slug((string) App\Services\Infrastructure\DopplerService::get('APP_NAME', 'laravel')).'-database-'),
            'persistent' => App\Services\Infrastructure\DopplerService::get('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => App\Services\Infrastructure\DopplerService::get('REDIS_URL'),
            'host' => App\Services\Infrastructure\DopplerService::get('REDIS_HOST', '127.0.0.1'),
            'username' => App\Services\Infrastructure\DopplerService::get('REDIS_USERNAME'),
            'password' => App\Services\Infrastructure\DopplerService::get('REDIS_PASSWORD'),
            'port' => App\Services\Infrastructure\DopplerService::get('REDIS_PORT', '6379'),
            'database' => App\Services\Infrastructure\DopplerService::get('REDIS_DB', '0'),
            'max_retries' => App\Services\Infrastructure\DopplerService::get('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => App\Services\Infrastructure\DopplerService::get('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => App\Services\Infrastructure\DopplerService::get('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => App\Services\Infrastructure\DopplerService::get('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => App\Services\Infrastructure\DopplerService::get('REDIS_URL'),
            'host' => App\Services\Infrastructure\DopplerService::get('REDIS_HOST', '127.0.0.1'),
            'username' => App\Services\Infrastructure\DopplerService::get('REDIS_USERNAME'),
            'password' => App\Services\Infrastructure\DopplerService::get('REDIS_PASSWORD'),
            'port' => App\Services\Infrastructure\DopplerService::get('REDIS_PORT', '6379'),
            'database' => App\Services\Infrastructure\DopplerService::get('REDIS_CACHE_DB', '1'),
            'max_retries' => App\Services\Infrastructure\DopplerService::get('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => App\Services\Infrastructure\DopplerService::get('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => App\Services\Infrastructure\DopplerService::get('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => App\Services\Infrastructure\DopplerService::get('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
