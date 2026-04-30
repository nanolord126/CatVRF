<?php

declare(strict_types=1);

namespace Modules\BigData;

use Illuminate\Support\ServiceProvider;
use Modules\BigData\Application\Services\BigDataFacade;
use Modules\BigData\Application\Services\BigDataService;
use Modules\BigData\Domain\DTOs\EventValidator;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseClient;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseService;
use Modules\BigData\Infrastructure\Kafka\KafkaProducerService;

class BigDataServiceProvider extends ServiceProvider
{
    protected array $deferred = [];

    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/config/bigdata.php',
            'bigdata',
        );

        // Register Observability (monitoring, metrics, tracing)
        if (config('bigdata.monitoring.enabled', true)) {
            $this->app->register(ObservabilityServiceProvider::class);
        }

        // Register Cost Monitoring / FinOps
        if (config('bigdata.cost.enabled', true)) {
            $this->app->register(CostMonitoringServiceProvider::class);
        }

        // ClickHouse Client (HTTP-based, no PDO driver needed)
        $this->app->singleton(ClickHouseClient::class, function () {
            return new ClickHouseClient(
                host: config('bigdata.clickhouse.host', 'localhost'),
                port: config('bigdata.clickhouse.port', 8123),
                database: config('bigdata.clickhouse.database', 'catvrf_bigdata'),
                username: config('bigdata.clickhouse.username', 'default'),
                password: config('bigdata.clickhouse.password', ''),
                connectTimeout: config('bigdata.clickhouse.connect_timeout', 5),
                queryTimeout: config('bigdata.clickhouse.query_timeout', 30),
            );
        });

        // ClickHouse Service
        $this->app->singleton(ClickHouseService::class, function ($app) {
            return new ClickHouseService(
                $app->make(ClickHouseClient::class),
            );
        });

        // Kafka Producer Service (supports Confluent REST Proxy + Redis Streams fallback)
        $this->app->singleton(KafkaProducerService::class, function () {
            return new KafkaProducerService(
                brokers: config('bigdata.kafka.brokers', 'localhost:9092'),
                topic: config('bigdata.kafka.topic', 'bigdata_events'),
                username: config('bigdata.kafka.username'),
                password: config('bigdata.kafka.password'),
                timeoutMs: config('bigdata.kafka.timeout_ms', 10000),
                restProxyUrl: config('bigdata.kafka.rest_proxy_url'),
                clusterId: config('bigdata.kafka.cluster_id'),
            );
        });

        // Event Validator
        $this->app->singleton(EventValidator::class);

        // Big Data Service (main orchestrator)
        $this->app->singleton(BigDataService::class, function ($app) {
            return new BigDataService(
                kafkaProducer: $app->make(KafkaProducerService::class),
                clickHouse: $app->make(ClickHouseService::class),
                validator: $app->make(EventValidator::class),
                enableKafka: config('bigdata.kafka.enabled', true),
            );
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/config/bigdata.php' => config_path('bigdata.php'),
        ], 'bigdata-config');

        // Register facade
        $this->app->alias(BigDataService::class, 'BigData');
    }
}
