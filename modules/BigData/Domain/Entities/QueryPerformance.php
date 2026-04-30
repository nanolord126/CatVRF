<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\QueryPerformanceStatus;

/**
 * Query Performance Entity
 *
 * Tracks ClickHouse query performance for BigData workloads.
 * Domain invariants:
 * - Latency values must be >= 0 (or -1 for unknown)
 * - QPS must be >= 0
 * - Status is derived from P95 latency, not set arbitrarily
 */
final class QueryPerformance
{
    private function __construct(
        public readonly string $queryName,
        public readonly float $p50LatencyMs,
        public readonly float $p95LatencyMs,
        public readonly float $p99LatencyMs,
        public readonly int $queriesPerSecond,
        public readonly int $slowQueries,
        public readonly float $avgRowsScanned,
        public readonly float $avgBytesScanned,
        public readonly QueryPerformanceStatus $status,
        public readonly CarbonImmutable $calculatedAt,
    ) {
        $this->validate();
    }

    /**
     * Factory: create from ClickHouse system.query_log metrics
     */
    public static function fromQueryLogMetrics(
        string $queryName,
        float $p50Ms,
        float $p95Ms,
        float $p99Ms,
        int $qps,
        int $slowQueries,
        float $avgRows,
        float $avgBytes,
        ?float $degradedMs = null,
        ?float $slowMs = null,
    ): self {
        $degraded = $degradedMs ?? QueryPerformanceStatus::defaultP95Thresholds()[$queryName] ?? 2000.0;
        $slow = $slowMs ?? 5000.0;

        $status = QueryPerformanceStatus::fromP95Latency($p95Ms, $degraded, $slow);

        return new self(
            queryName: $queryName,
            p50LatencyMs: round($p50Ms, 2),
            p95LatencyMs: round($p95Ms, 2),
            p99LatencyMs: round($p99Ms, 2),
            queriesPerSecond: $qps,
            slowQueries: $slowQueries,
            avgRowsScanned: round($avgRows, 0),
            avgBytesScanned: round($avgBytes, 0),
            status: $status,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Factory: create when query metrics are unavailable
     */
    public static function unknown(string $queryName): self
    {
        return new self(
            queryName: $queryName,
            p50LatencyMs: -1.0,
            p95LatencyMs: -1.0,
            p99LatencyMs: -1.0,
            queriesPerSecond: 0,
            slowQueries: -1,
            avgRowsScanned: -1.0,
            avgBytesScanned: -1.0,
            status: QueryPerformanceStatus::Unknown,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Factory: from stored snapshot
     */
    public static function fromArray(array $data): self
    {
        return new self(
            queryName: $data['query_name'],
            p50LatencyMs: $data['p50_latency_ms'],
            p95LatencyMs: $data['p95_latency_ms'],
            p99LatencyMs: $data['p99_latency_ms'],
            queriesPerSecond: $data['queries_per_second'],
            slowQueries: $data['slow_queries'],
            avgRowsScanned: $data['avg_rows_scanned'],
            avgBytesScanned: $data['avg_bytes_scanned'],
            status: QueryPerformanceStatus::from($data['status']),
            calculatedAt: CarbonImmutable::parse($data['calculated_at']),
        );
    }

    /**
     * Business rule: is the query performance acceptable for seller dashboards?
     * Seller dashboards require P95 < 2s
     */
    public function isAcceptableForDashboards(): bool
    {
        return $this->p95LatencyMs >= 0 && $this->p95LatencyMs < 2000.0;
    }

    /**
     * Business rule: is this a heavy query (scans > 1M rows)?
     */
    public function isHeavyQuery(): bool
    {
        return $this->avgRowsScanned > 1_000_000;
    }

    /**
     * Business rule: data scanned in human-readable format
     */
    public function avgBytesScannedHuman(): string
    {
        if ($this->avgBytesScanned < 0) {
            return 'unknown';
        }

        return match (true) {
            $this->avgBytesScanned >= 1_073_741_824 => round($this->avgBytesScanned / 1_073_741_824, 2) . ' GB',
            $this->avgBytesScanned >= 1_048_576 => round($this->avgBytesScanned / 1_048_576, 2) . ' MB',
            $this->avgBytesScanned >= 1024 => round($this->avgBytesScanned / 1024, 2) . ' KB',
            default => round($this->avgBytesScanned, 0) . ' B',
        };
    }

    /**
     * Business rule: slow query ratio (slow / total)
     */
    public function slowQueryRatio(): ?float
    {
        if ($this->queriesPerSecond <= 0 || $this->slowQueries < 0) {
            return null;
        }

        // Approximate: slow queries in the measurement window vs total
        $totalApprox = $this->queriesPerSecond * 60; // 1-minute window
        if ($totalApprox <= 0) {
            return null;
        }

        return round($this->slowQueries / $totalApprox, 4);
    }

    /**
     * Business rule: compare with previous measurement
     */
    public function isSlowerThan(self $previous): bool
    {
        return $this->p95LatencyMs > $previous->p95LatencyMs;
    }

    /**
     * Business rule: P95 latency as % of threshold
     */
    public function p95AsPercentOfThreshold(float $thresholdMs = 2000.0): float
    {
        if ($this->p95LatencyMs < 0 || $thresholdMs <= 0) {
            return -1.0;
        }

        return round(($this->p95LatencyMs / $thresholdMs) * 100, 1);
    }

    public function isOptimal(): bool
    {
        return $this->status === QueryPerformanceStatus::Optimal;
    }

    public function toArray(): array
    {
        return [
            'query_name' => $this->queryName,
            'p50_latency_ms' => $this->p50LatencyMs,
            'p95_latency_ms' => $this->p95LatencyMs,
            'p99_latency_ms' => $this->p99LatencyMs,
            'queries_per_second' => $this->queriesPerSecond,
            'slow_queries' => $this->slowQueries,
            'slow_query_ratio' => $this->slowQueryRatio(),
            'avg_rows_scanned' => $this->avgRowsScanned,
            'avg_bytes_scanned' => $this->avgBytesScanned,
            'avg_bytes_scanned_human' => $this->avgBytesScannedHuman(),
            'is_heavy_query' => $this->isHeavyQuery(),
            'status' => $this->status->value,
            'calculated_at' => $this->calculatedAt->toIso8601String(),
        ];
    }

    private function validate(): void
    {
        if (trim($this->queryName) === '') {
            throw new \InvalidArgumentException('QueryPerformance queryName cannot be empty');
        }

        if ($this->p50LatencyMs < -1 || $this->p95LatencyMs < -1 || $this->p99LatencyMs < -1) {
            throw new \InvalidArgumentException('QueryPerformance latency values must be >= -1');
        }

        if ($this->queriesPerSecond < 0) {
            throw new \InvalidArgumentException('QueryPerformance queriesPerSecond must be >= 0');
        }
    }
}
