<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use ClickHouse\Client;
use ClickHouse\Settings;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Str;

final class ClickHouseService
{
    private Client $client;
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        $settings = new Settings([
            Settings::MAX_EXECUTION_TIME => 30,
            Settings::CONNECT_TIMEOUT => 10,
            Settings::READ_TIMEOUT => 30,
            Settings::WRITE_TIMEOUT => 30,
        ]);

        $this->client = new Client(
            [
                'host' => config('clickhouse.host', 'localhost'),
                'port' => config('clickhouse.port', 8123),
                'username' => config('clickhouse.username', 'default'),
                'password' => config('clickhouse.password', ''),
                'database' => config('clickhouse.database', 'analytics'),
            ],
            $settings,
        );

        $this->log->channel('analytics')->debug('[ClickHouse] Client initialized', [
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Insert geo activity events to ClickHouse
     */
    public function insertGeoEvents(Collection $events): void
    {
        if ($events->isEmpty()) {
            return;
        }

        $rows = [];

        foreach ($events as $event) {
            $rows[] = [
                'id' => (string) $event->uuid,
                'tenant_id' => (int) $event->tenant_id,
                'vertical' => (string) $event->vertical,
                'event_type' => $event->type ?? 'view',
                'latitude' => (float) $event->latitude,
                'longitude' => (float) $event->longitude,
                'geo_hash' => $this->calculateGeoHash($event->latitude, $event->longitude),
                'user_id' => $event->user_id ? (int) $event->user_id : null,
                'session_id' => (string) $event->session_id,
                'device_type' => $event->device_type ?? 'unknown',
                'browser' => $event->browser ?? 'unknown',
                'country_code' => $event->country_code ?? 'XX',
                'created_at' => $event->created_at->toDateTime(),
                'correlation_id' => (string) $this->correlationId,
            ];
        }

        try {
            $this->client->insert('ch_geo_events', $rows);

            $this->log->channel('audit')->info('[ClickHouse] Geo events inserted', [
                'count' => count($rows),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Exception $e) {
            $this->log->channel('error')->error('[ClickHouse] Insert geo events failed', [
                'error' => $e->getMessage(),
                'count' => count($rows),
                'correlation_id' => $this->correlationId,
                'stacktrace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Insert click events to ClickHouse
     */
    public function insertClickEvents(Collection $events): void
    {
        if ($events->isEmpty()) {
            return;
        }

        $rows = [];

        foreach ($events as $event) {
            $rows[] = [
                'id' => (string) $event->uuid,
                'tenant_id' => (int) $event->tenant_id,
                'vertical' => (string) $event->vertical,
                'page_url' => (string) $event->page_url,
                'x_coordinate' => (int) $event->x ?? 0,
                'y_coordinate' => (int) $event->y ?? 0,
                'click_duration_ms' => (int) ($event->duration_ms ?? 0),
                'element_selector' => $event->selector ?? null,
                'element_type' => $event->element_type ?? 'unknown',
                'user_id' => $event->user_id ? (int) $event->user_id : null,
                'session_id' => (string) $event->session_id,
                'device_type' => $event->device_type ?? 'unknown',
                'viewport_width' => (int) ($event->viewport_width ?? 0),
                'viewport_height' => (int) ($event->viewport_height ?? 0),
                'created_at' => $event->created_at->toDateTime(),
                'correlation_id' => (string) $this->correlationId,
            ];
        }

        try {
            $this->client->insert('ch_click_events', $rows);

            $this->log->channel('audit')->info('[ClickHouse] Click events inserted', [
                'count' => count($rows),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Exception $e) {
            $this->log->channel('error')->error('[ClickHouse] Insert click events failed', [
                'error' => $e->getMessage(),
                'count' => count($rows),
                'correlation_id' => $this->correlationId,
                'stacktrace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Query hourly aggregated geo heatmap
     *
     * @param int $tenantId
     * @param string $vertical
     * @param string $fromDate
     * @param string $toDate
     * @param string $metric
     * @return array
     */
    public function queryGeoHourly(
        int $tenantId,
        string $vertical,
        string $fromDate,
        string $toDate,
        string $metric = 'event_count'
    ): array {
        $query = <<<SQL
        SELECT
            toStartOfHour(created_at) as period,
            geo_hash,
            event_type,
            COUNT(*) as event_count,
            uniq(user_id) as unique_users,
            uniq(session_id) as unique_sessions
        FROM ch_geo_events
        WHERE tenant_id = {$tenantId}
          AND vertical = '{$vertical}'
          AND created_at >= '{$fromDate}'
          AND created_at < '{$toDate}'
        GROUP BY period, geo_hash, event_type
        ORDER BY period, event_count DESC
        LIMIT 10000
        SQL;

        return $this->executeQuery($query);
    }

    /**
     * Query daily aggregated geo heatmap
     */
    public function queryGeoDaily(
        int $tenantId,
        string $vertical,
        string $fromDate,
        string $toDate,
        string $metric = 'event_count'
    ): array {
        $query = <<<SQL
        SELECT
            toDate(created_at) as period,
            geo_hash,
            COUNT(*) as event_count,
            uniq(user_id) as unique_users,
            avg(latitude) as avg_latitude,
            avg(longitude) as avg_longitude
        FROM ch_geo_events
        WHERE tenant_id = {$tenantId}
          AND vertical = '{$vertical}'
          AND created_at >= '{$fromDate}'
          AND created_at < '{$toDate}'
        GROUP BY period, geo_hash
        ORDER BY period, event_count DESC
        LIMIT 10000
        SQL;

        return $this->executeQuery($query);
    }

    /**
     * Query weekly aggregated geo heatmap
     */
    public function queryGeoWeekly(
        int $tenantId,
        string $vertical,
        string $fromDate,
        string $toDate
    ): array {
        $query = <<<SQL
        SELECT
            toStartOfWeek(created_at) as period,
            geo_hash,
            COUNT(*) as event_count,
            uniq(user_id) as unique_users,
            uniq(session_id) as unique_sessions,
            avg(latitude) as avg_latitude,
            avg(longitude) as avg_longitude
        FROM ch_geo_events
        WHERE tenant_id = {$tenantId}
          AND vertical = '{$vertical}'
          AND created_at >= '{$fromDate}'
          AND created_at < '{$toDate}'
        GROUP BY period, geo_hash
        ORDER BY period, event_count DESC
        LIMIT 10000
        SQL;

        return $this->executeQuery($query);
    }

    /**
     * Query hourly aggregated click heatmap
     */
    public function queryClickHourly(
        int $tenantId,
        string $vertical,
        string $pageUrl,
        string $fromDate,
        string $toDate
    ): array {
        $query = <<<SQL
        SELECT
            toStartOfHour(created_at) as period,
            device_type,
            COUNT(*) as click_count,
            uniq(user_id) as unique_users,
            avg(x_coordinate) as avg_x,
            avg(y_coordinate) as avg_y
        FROM ch_click_events
        WHERE tenant_id = {$tenantId}
          AND vertical = '{$vertical}'
          AND page_url = '{$pageUrl}'
          AND created_at >= '{$fromDate}'
          AND created_at < '{$toDate}'
        GROUP BY period, device_type
        ORDER BY period, click_count DESC
        LIMIT 10000
        SQL;

        return $this->executeQuery($query);
    }

    /**
     * Query daily aggregated click heatmap
     */
    public function queryClickDaily(
        int $tenantId,
        string $vertical,
        string $pageUrl,
        string $fromDate,
        string $toDate
    ): array {
        $query = <<<SQL
        SELECT
            toDate(created_at) as period,
            COUNT(*) as click_count,
            uniq(user_id) as unique_users,
            avg(x_coordinate) as avg_x,
            avg(y_coordinate) as avg_y,
            max(viewport_width) as viewport_width,
            max(viewport_height) as viewport_height
        FROM ch_click_events
        WHERE tenant_id = {$tenantId}
          AND vertical = '{$vertical}'
          AND page_url = '{$pageUrl}'
          AND created_at >= '{$fromDate}'
          AND created_at < '{$toDate}'
        GROUP BY period
        ORDER BY period, click_count DESC
        LIMIT 10000
        SQL;

        return $this->executeQuery($query);
    }

    /**
     * Compare two time periods for geo heatmap
     */
    public function compareGeoHeatmap(
        int $tenantId,
        string $vertical,
        string $period1From,
        string $period1To,
        string $period2From,
        string $period2To
    ): array {
        $query = <<<SQL
        SELECT
            geo_hash,
            
            -- Period 1
            sum(created_at >= '{$period1From}' AND created_at < '{$period1To}' ? 1 : 0) as period1_count,
            sum(created_at >= '{$period1From}' AND created_at < '{$period1To}' ? 1 : 0) / 
                sum(created_at >= '{$period2From}' AND created_at < '{$period2To}' ? 1 : 0) as delta_percent,
            
            -- Period 2
            sum(created_at >= '{$period2From}' AND created_at < '{$period2To}' ? 1 : 0) as period2_count
        FROM ch_geo_events
        WHERE tenant_id = {$tenantId}
          AND vertical = '{$vertical}'
          AND (
              (created_at >= '{$period1From}' AND created_at < '{$period1To}')
              OR (created_at >= '{$period2From}' AND created_at < '{$period2To}')
          )
        GROUP BY geo_hash
        ORDER BY period1_count DESC
        LIMIT 5000
        SQL;

        return $this->executeQuery($query);
    }

    /**
     * Get health status of ClickHouse
     */
    public function health(): array
    {
        try {
            $query = 'SELECT 1 as status';
            $result = $this->executeQuery($query);

            return [
                'status' => 'healthy',
                'latency_ms' => 0,
                'correlation_id' => $this->correlationId,
            ];
        } catch (Exception $e) {
            $this->log->channel('error')->error('[ClickHouse] Health check failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ];
        }
    }

    /**
     * Execute raw query
     */
    private function executeQuery(string $query): array
    {
        try {
            $startTime = microtime(true);
            $statement = $this->client->query($query);
            $result = $statement->fetchAll();
            $executionTime = (microtime(true) - $startTime) * 1000;

            $this->log->channel('analytics')->debug('[ClickHouse] Query executed', [
                'execution_time_ms' => $executionTime,
                'result_count' => count($result),
                'correlation_id' => $this->correlationId,
            ]);

            return $result;
        } catch (Exception $e) {
            $this->log->channel('error')->error('[ClickHouse] Query execution failed', [
                'error' => $e->getMessage(),
                'query' => substr($query, 0, 200),
                'correlation_id' => $this->correlationId,
                'stacktrace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate geohash (simplified version)
     * Production: use proper geohash library
     */
    private function calculateGeoHash(float $latitude, float $longitude): string
    {
        // Simplified: round to 2 decimal places
        $lat = (int) ($latitude * 100);
        $lon = (int) ($longitude * 100);

        return dechex($lat & 0xFFFF) . dechex($lon & 0xFFFF);
    }

    public function setCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }
}
