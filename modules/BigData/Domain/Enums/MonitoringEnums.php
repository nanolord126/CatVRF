<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Enums;

/**
 * BigData Monitoring Enumerations
 *
 * All domain enums for the monitoring subsystem in one file.
 * Each enum includes helper methods for status transitions, comparisons,
 * and Prometheus label mapping.
 */

// ============================================================================
// Pipeline Health Status
// ============================================================================

enum HealthStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Critical = 'critical';
    case Unknown = 'unknown';

    public function isHealthy(): bool
    {
        return $this === self::Healthy;
    }

    public function isDegraded(): bool
    {
        return $this === self::Degraded;
    }

    public function isCritical(): bool
    {
        return $this === self::Critical;
    }

    public function isUnknown(): bool
    {
        return $this === self::Unknown;
    }

    /**
     * Determine status from lag seconds and configured thresholds
     */
    public static function fromLagSeconds(float $lagSeconds, float $degradedThreshold = 60.0, float $criticalThreshold = 300.0): self
    {
        return match (true) {
            $lagSeconds < 0 => self::Unknown,
            $lagSeconds >= $criticalThreshold => self::Critical,
            $lagSeconds >= $degradedThreshold => self::Degraded,
            default => self::Healthy,
        };
    }

    /**
     * Can transition to a more severe status but not back without explicit resolution
     */
    public function canTransitionTo(self $target): bool
    {
        $severity = [self::Healthy->value => 0, self::Degraded->value => 1, self::Critical->value => 2, self::Unknown->value => 3];
        $targetLevel = $severity[$target->value] ?? 0;
        $currentLevel = $severity[$this->value] ?? 0;

        return (bool) ($targetLevel >= $currentLevel);
    }

    /**
     * Map to Prometheus alert severity label
     */
    public function toPrometheusSeverity(): string
    {
        return match ($this) {
            self::Healthy => 'ok',
            self::Degraded => 'warning',
            self::Critical => 'critical',
            self::Unknown => 'unknown',
        };
    }

    /**
     * Map to Grafana panel color
     */
    public function toColor(): string
    {
        return match ($this) {
            self::Healthy => 'green',
            self::Degraded => 'yellow',
            self::Critical => 'red',
            self::Unknown => 'gray',
        };
    }
}

// ============================================================================
// Data Freshness Status
// ============================================================================

enum FreshnessStatus: string
{
    case Fresh = 'fresh';
    case Stale = 'stale';
    case Expired = 'expired';
    case NoData = 'no_data';
    case Unknown = 'unknown';

    public function isFresh(): bool
    {
        return $this === self::Fresh;
    }

    public function isStale(): bool
    {
        return $this === self::Stale;
    }

    public function isExpired(): bool
    {
        return $this === self::Expired;
    }

    public function isNoData(): bool
    {
        return $this === self::NoData;
    }

    /**
     * Determine freshness status from hours since last record
     * Uses configurable thresholds: fresh < 50% of threshold, stale < 100%, expired >= 100%
     */
    public static function fromFreshnessHours(float $hours, float $thresholdHours): self
    {
        if ($hours < 0) {
            return self::Unknown;
        }

        return match (true) {
            $hours <= $thresholdHours * 0.5 => self::Fresh,
            $hours <= $thresholdHours => self::Stale,
            default => self::Expired,
        };
    }

    /**
     * Default freshness thresholds per table (hours)
     * @return array<string, float>
     */
    public static function defaultThresholds(): array
    {
        return [
            'raw_events' => 1.0,
            'daily_metrics' => 4.0,
            'seller_metrics' => 4.0,
            'clv_predictions' => 24.0,
            'abtest_results' => 24.0,
            'buyer_seller_features' => 24.0,
            'events_hourly' => 2.0,
        ];
    }

    /**
     * Get threshold for a table name
     */
    public static function thresholdForTable(string $tableName): float
    {
        return self::defaultThresholds()[$tableName] ?? 4.0;
    }

    /**
     * Map to Prometheus alert severity
     */
    public function toAlertSeverity(): string
    {
        return match ($this) {
            self::Fresh => 'ok',
            self::Stale => 'warning',
            self::Expired => 'critical',
            self::NoData => 'critical',
            self::Unknown => 'unknown',
        };
    }
}

// ============================================================================
// CLV Model Drift Status
// ============================================================================

enum DriftStatus: string
{
    case None = 'none';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
    case Unknown = 'unknown';

    public function hasDrift(): bool
    {
        return $this !== self::None && $this !== self::Unknown;
    }

    public function isCritical(): bool
    {
        return $this === self::Critical;
    }

    public function isHigh(): bool
    {
        return $this === self::High;
    }

    /**
     * Determine drift status from accuracy drop percentage
     * Thresholds: none < 1%, low < 3%, medium < 5%, high < 8%, critical >= 8%
     */
    public static function fromAccuracyDrop(float $drop): self
    {
        if ($drop < 0) {
            return self::Unknown;
        }

        return match (true) {
            $drop < 0.01 => self::None,
            $drop < 0.03 => self::Low,
            $drop < 0.05 => self::Medium,
            $drop < 0.08 => self::High,
            default => self::Critical,
        };
    }

    /**
     * Determine drift from PSI (Population Stability Index)
     * PSI < 0.1 = no drift, 0.1-0.2 = low, 0.2-0.5 = medium, > 0.5 = critical
     */
    public static function fromPSI(float $psi): self
    {
        if ($psi < 0) {
            return self::Unknown;
        }

        return match (true) {
            $psi < 0.1 => self::None,
            $psi < 0.2 => self::Low,
            $psi < 0.5 => self::Medium,
            default => self::Critical,
        };
    }

    /**
     * Does this drift status require immediate model retraining?
     */
    public function requiresRetraining(): bool
    {
        return in_array($this, [self::High, self::Critical], true);
    }

    /**
     * Does this drift status require scheduling a retraining?
     */
    public function requiresScheduledRetraining(): bool
    {
        return in_array($this, [self::Medium, self::High, self::Critical], true);
    }

    /**
     * Map to Prometheus alert severity
     */
    public function toAlertSeverity(): string
    {
        return match ($this) {
            self::None => 'ok',
            self::Low => 'info',
            self::Medium => 'warning',
            self::High => 'warning',
            self::Critical => 'critical',
            self::Unknown => 'unknown',
        };
    }
}

// ============================================================================
// Alert Severity
// ============================================================================

enum AlertSeverity: string
{
    case Ok = 'ok';
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';
    case Unknown = 'unknown';

    public function isFiring(): bool
    {
        return $this !== self::Ok && $this !== self::Unknown;
    }

    public function isCritical(): bool
    {
        return $this === self::Critical;
    }

    public function isWarning(): bool
    {
        return $this === self::Warning;
    }

    /**
     * Compare severity levels: higher = more severe
     */
    public function isMoreSevereThan(self $other): bool
    {
        $levels = [self::Ok => 0, self::Info => 1, self::Warning => 2, self::Critical => 3, self::Unknown => -1];

        return ($levels[$this->value] ?? -1) > ($levels[$other->value] ?? -1);
    }

    /**
     * Get the most severe of two severities
     */
    public static function mostSevere(self $a, self $b): self
    {
        return $a->isMoreSevereThan($b) ? $a : $b;
    }

    /**
     * Map to Alertmanager routing group
     */
    public function toAlertmanagerGroup(): string
    {
        return match ($this) {
            self::Critical => 'bigdata-critical',
            self::Warning, self::Info => 'bigdata-alerts',
            default => 'default',
        };
    }

    /**
     * Map to SRE escalation level
     */
    public function toEscalationLevel(): int
    {
        return match ($this) {
            self::Ok => 0,
            self::Info => 0,
            self::Warning => 1,
            self::Critical => 2,
            self::Unknown => 1,
        };
    }

    /**
     * Expected response time per severity
     */
    public function expectedResponseMinutes(): int
    {
        return match ($this) {
            self::Critical => 15,
            self::Warning => 60,
            self::Info => 240,
            default => 0,
        };
    }
}

// ============================================================================
// Query Performance Status
// ============================================================================

enum QueryPerformanceStatus: string
{
    case Optimal = 'optimal';
    case Degraded = 'degraded';
    case Slow = 'slow';
    case Unknown = 'unknown';

    public function isOptimal(): bool
    {
        return $this === self::Optimal;
    }

    /**
     * Determine from P95 latency in milliseconds
     */
    public static function fromP95Latency(float $p95Ms, float $degradedMs = 2000.0, float $slowMs = 5000.0): self
    {
        if ($p95Ms < 0) {
            return self::Unknown;
        }

        return match (true) {
            $p95Ms < $degradedMs => self::Optimal,
            $p95Ms < $slowMs => self::Degraded,
            default => self::Slow,
        };
    }

    /**
     * Default P95 thresholds per query type (ms)
     * @return array<string, float>
     */
    public static function defaultP95Thresholds(): array
    {
        return [
            'seller_dashboard' => 1000.0,
            'clv_lookup' => 500.0,
            'abtest_results' => 2000.0,
            'top_products' => 3000.0,
            'raw_events_scan' => 5000.0,
            'feature_store' => 2000.0,
        ];
    }
}

// ============================================================================
// ClickHouse Table Type (for monitoring-specific queries)
// ============================================================================

enum ClickHouseTableType: string
{
    case RawEvents = 'ch_raw_events';
    case DailyMetrics = 'ch_daily_metrics';
    case SellerMetrics = 'ch_seller_daily_metrics';
    case CLVPredictions = 'ch_clv_predictions';
    case ABTestAssignments = 'ch_abtest_assignments';
    case ABTestResults = 'ch_abtest_results';
    case BuyerSellerFeatures = 'ch_buyer_seller_features';
    case EventsHourly = 'ch_events_hourly';

    /**
     * Get the friendly name for dashboards
     */
    public function friendlyName(): string
    {
        return match ($this) {
            self::RawEvents => 'raw_events',
            self::DailyMetrics => 'daily_metrics',
            self::SellerMetrics => 'seller_metrics',
            self::CLVPredictions => 'clv_predictions',
            self::ABTestAssignments => 'abtest_assignments',
            self::ABTestResults => 'abtest_results',
            self::BuyerSellerFeatures => 'buyer_seller_features',
            self::EventsHourly => 'events_hourly',
        };
    }

    /**
     * Get TTL in days for this table
     */
    public function retentionDays(): int
    {
        return match ($this) {
            self::RawEvents => 90,
            self::DailyMetrics => 730,
            self::SellerMetrics => 730,
            self::CLVPredictions => 365,
            self::ABTestAssignments => 180,
            self::ABTestResults => 365,
            self::BuyerSellerFeatures => 90,
            self::EventsHourly => 90,
        };
    }

    /**
     * Get freshness threshold in hours
     */
    public function freshnessThresholdHours(): float
    {
        return FreshnessStatus::thresholdForTable($this->friendlyName());
    }

    /**
     * All tables as array for iteration
     * @return array<self>
     */
    public static function monitoredTables(): array
    {
        return [
            self::RawEvents,
            self::DailyMetrics,
            self::SellerMetrics,
            self::CLVPredictions,
            self::ABTestResults,
            self::BuyerSellerFeatures,
        ];
    }
}
