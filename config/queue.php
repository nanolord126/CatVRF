<?php

declare(strict_types=1);

use App\Services\Infrastructure\DopplerService;

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

    'default' => DopplerService::get('QUEUE_CONNECTION', 'database'),

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
            'connection' => DopplerService::get('DB_QUEUE_CONNECTION'),
            'table' => DopplerService::get('DB_QUEUE_TABLE', 'jobs'),
            'queue' => DopplerService::get('DB_QUEUE', 'default'),
            'retry_after' => (int) DopplerService::get('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => DopplerService::get('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => DopplerService::get('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) DopplerService::get('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => DopplerService::get('AWS_ACCESS_KEY_ID'),
            'secret' => DopplerService::get('AWS_SECRET_ACCESS_KEY'),
            'prefix' => DopplerService::get('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => DopplerService::get('SQS_QUEUE', 'default'),
            'suffix' => DopplerService::get('SQS_SUFFIX'),
            'region' => DopplerService::get('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => DopplerService::get('REDIS_QUEUE', 'default'),
            'retry_after' => (int) DopplerService::get('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

        /*
         * EMERGENCY QUEUE - Highest Priority
         * For critical operations: emergency medical alerts, critical payment failures
         */
        'emergency' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'emergency',
            'retry_after' => 30,
            'block_for' => null,
            'after_commit' => true,
        ],

        /*
         * PAYMENT WEBHOOK QUEUE - High Priority
         * For payment webhooks from payment providers
         */
        'payment-webhook' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'payment-webhook',
            'retry_after' => 60,
            'block_for' => null,
            'after_commit' => true,
        ],

        /*
         * PAYMENT QUEUE - High Priority
         * For payment processing, confirmations
         */
        'payment' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'payment',
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => true,
        ],

        /*
         * FRAUD CHECK PAYMENT QUEUE - High Priority
         * For fraud detection on payments
         */
        'fraud-check-payment' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'fraud-check-payment',
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        /*
         * NOTIFICATION QUEUE - Medium Priority
         * For all notifications (email, SMS, push)
         */
        'notification' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'notification',
            'retry_after' => 120,
            'block_for' => null,
            'after_commit' => false,
        ],

        /*
         * AUDIT QUEUE - Medium Priority
         * For audit logging, compliance tracking
         */
        'audit' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'audit',
            'retry_after' => 120,
            'block_for' => null,
            'after_commit' => false,
        ],

        /*
         * ML RECALCULATE QUEUE - Low Priority, High Memory
         * For ML model training, recalculation, inference
         */
        'ml-recalculate' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'ml-recalculate',
            'retry_after' => 600,
            'block_for' => null,
            'after_commit' => false,
        ],

        /*
         * DELIVERY QUEUE - Medium Priority
         * For delivery assignment, route optimization
         */
        'delivery' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'delivery',
            'retry_after' => 180,
            'block_for' => null,
            'after_commit' => false,
        ],

        /*
         * SUPERMARKET-HIGH QUEUE - High Priority
         * For supermarket checkout, inventory reservation, cold chain monitoring
         */
        'supermarket-high' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'supermarket-high',
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => true,
        ],

        /*
         * BULK QUEUE - Low Priority
         * For bulk imports, reports, exports
         */
        'bulk' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'bulk',
            'retry_after' => 300,
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
        'database' => DopplerService::get('DB_CONNECTION', 'sqlite'),
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
        'driver' => DopplerService::get('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => DopplerService::get('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Queue Configuration
    |--------------------------------------------------------------------------
    | Dedicated queues for Filament heavy actions to prevent blocking
    */

    'filament' => [
        'heavy' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'filament-heavy',
            'retry_after' => 300,
            'block_for' => null,
            'after_commit' => false,
        ],
        'light' => [
            'driver' => 'redis',
            'connection' => DopplerService::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => 'filament-light',
            'retry_after' => 60,
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

];
