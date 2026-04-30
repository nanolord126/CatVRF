<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\FreshnessStatus;

/**
 * Data Freshness Entity
 *
 * Represents how stale data is for a specific ClickHouse table.
 * Domain invariants:
 * - Freshness hours must be >= 0 (or -1 for unknown/no data)
 * - Status is derived from hours vs threshold, not set arbitrarily
 * - Threshold must be > 0
 */
final class DataFreshness
{
    private function __construct(
        public readonly string $tableName,
        public readonly float $freshnessHours,
        public readonly float $freshnessMinutes,
        public readonly ?CarbonImmutable $lastRecordAt,
        public readonly FreshnessStatus $status,
        public readonly float $thresholdHours,
        public readonly CarbonImmutable $calculatedAt,
    ) {
        $this->validate();
    }

    /**
     * Factory: create from a last-record timestamp
     */
    public static function fromLastRecord(
        string $tableName,
        ?string $lastRecordTimestamp,
        ?float $thresholdHours = null,
    ): self {
        $threshold = $thresholdHours ?? FreshnessStatus::thresholdForTable($tableName);

        if ($lastRecordTimestamp === null) {
            return self::noData($tableName, $threshold);
        }

        $lastRecord = CarbonImmutable::parse($lastRecordTimestamp);
        $freshnessSeconds = CarbonImmutable::now()->diffInSeconds($lastRecord, absolute: true);
        $freshnessHours = round($freshnessSeconds / 3600, 2);
        $freshnessMinutes = round($freshnessSeconds / 60, 2);

        $status = FreshnessStatus::fromFreshnessHours($freshnessHours, $threshold);

        return new self(
            tableName: $tableName,
            freshnessHours: $freshnessHours,
            freshnessMinutes: $freshnessMinutes,
            lastRecordAt: $lastRecord,
            status: $status,
            thresholdHours: $threshold,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Factory: create when no data exists in the table
     */
    public static function noData(string $tableName, ?float $thresholdHours = null): self
    {
        return new self(
            tableName: $tableName,
            freshnessHours: -1.0,
            freshnessMinutes: -1.0,
            lastRecordAt: null,
            status: FreshnessStatus::NoData,
            thresholdHours: $thresholdHours ?? FreshnessStatus::thresholdForTable($tableName),
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Factory: create from stored snapshot
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tableName: $data['table_name'],
            freshnessHours: $data['freshness_hours'],
            freshnessMinutes: $data['freshness_minutes'],
            lastRecordAt: isset($data['last_record_timestamp'])
                ? CarbonImmutable::parse($data['last_record_timestamp'])
                : null,
            status: FreshnessStatus::from($data['status']),
            thresholdHours: $data['threshold_hours'],
            calculatedAt: CarbonImmutable::parse($data['calculated_at']),
        );
    }

    /**
     * Business rule: is the data usable for dashboards?
     * Data is usable if not expired and not missing
     */
    public function isUsableForDashboards(): bool
    {
        return in_array($this->status, [FreshnessStatus::Fresh, FreshnessStatus::Stale], true);
    }

    /**
     * Business rule: how many hours until data expires?
     */
    public function hoursUntilExpiry(): float
    {
        if ($this->freshnessHours < 0) {
            return 0.0;
        }

        return max(0.0, round($this->thresholdHours - $this->freshnessHours, 2));
    }

    /**
     * Business rule: percentage of threshold consumed
     * 0% = just updated, 100% = at threshold, >100% = expired
     */
    public function thresholdConsumedPercent(): float
    {
        if ($this->freshnessHours < 0 || $this->thresholdHours <= 0) {
            return 100.0;
        }

        return round(($this->freshnessHours / $this->thresholdHours) * 100, 1);
    }

    /**
     * Business rule: is this table approaching expiry? (>75% of threshold)
     */
    public function isApproachingExpiry(): bool
    {
        return $this->thresholdConsumedPercent() >= 75.0
            && $this->status !== FreshnessStatus::Expired
            && $this->status !== FreshnessStatus::NoData;
    }

    /**
     * Business rule: compare freshness with another table
     */
    public function isStalerThan(self $other): bool
    {
        return $this->freshnessHours > $other->freshnessHours;
    }

    public function isFresh(): bool
    {
        return $this->status === FreshnessStatus::Fresh;
    }

    public function isStale(): bool
    {
        return $this->status === FreshnessStatus::Stale;
    }

    public function isExpired(): bool
    {
        return $this->status === FreshnessStatus::Expired;
    }

    public function toArray(): array
    {
        return [
            'table_name' => $this->tableName,
            'freshness_hours' => $this->freshnessHours,
            'freshness_minutes' => $this->freshnessMinutes,
            'last_record_timestamp' => $this->lastRecordAt?->toIso8601String(),
            'status' => $this->status->value,
            'threshold_hours' => $this->thresholdHours,
            'hours_until_expiry' => $this->hoursUntilExpiry(),
            'threshold_consumed_percent' => $this->thresholdConsumedPercent(),
            'is_usable_for_dashboards' => $this->isUsableForDashboards(),
            'calculated_at' => $this->calculatedAt->toIso8601String(),
        ];
    }

    private function validate(): void
    {
        if (trim($this->tableName) === '') {
            throw new \InvalidArgumentException('DataFreshness tableName cannot be empty');
        }

        if ($this->freshnessHours < -1) {
            throw new \InvalidArgumentException('DataFreshness freshnessHours must be >= -1');
        }

        if ($this->thresholdHours <= 0) {
            throw new \InvalidArgumentException('DataFreshness thresholdHours must be > 0');
        }
    }
}
