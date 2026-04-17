<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => App\Services\Infrastructure\DopplerService::get('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis",
    |          "deferred", "background", "failover", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => App\Services\Infrastructure\DopplerService::get('DB_QUEUE_CONNECTION'),
            'table' => App\Services\Infrastructure\DopplerService::get('DB_QUEUE_TABLE', 'jobs'),
            'queue' => App\Services\Infrastructure\DopplerService::get('DB_QUEUE', 'default'),
            'retry_after' => (int) App\Services\Infrastructure\DopplerService::get('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => App\Services\Infrastructure\DopplerService::get('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => App\Services\Infrastructure\DopplerService::get('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) App\Services\Infrastructure\DopplerService::get('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => App\Services\Infrastructure\DopplerService::get('AWS_ACCESS_KEY_ID'),
            'secret' => App\Services\Infrastructure\DopplerService::get('AWS_SECRET_ACCESS_KEY'),
            'prefix' => App\Services\Infrastructure\DopplerService::get('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => App\Services\Infrastructure\DopplerService::get('SQS_QUEUE', 'default'),
            'suffix' => App\Services\Infrastructure\DopplerService::get('SQS_SUFFIX'),
            'region' => App\Services\Infrastructure\DopplerService::get('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => App\Services\Infrastructure\DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => App\Services\Infrastructure\DopplerService::get('REDIS_QUEUE', 'default'),
            'retry_after' => (int) App\Services\Infrastructure\DopplerService::get('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

        'payment-fraud-high-priority' => [
            'driver' => 'redis',
            'connection' => App\Services\Infrastructure\DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => App\Services\Infrastructure\DopplerService::get('REDIS_QUEUE', 'default') . ':payment-fraud-high',
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        'deferred' => [
            'driver' => 'deferred',
        ],

        'background' => [
            'driver' => 'background',
        ],

        'failover' => [
            'driver' => 'failover',
            'connections' => [
                'database',
                'deferred',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => App\Services\Infrastructure\DopplerService::get('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => App\Services\Infrastructure\DopplerService::get('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => App\Services\Infrastructure\DopplerService::get('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];
