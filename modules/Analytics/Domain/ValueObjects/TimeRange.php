<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/**
 * Time Range Value Object
 *
 * Represents a specific time range for real-time analytics (e.g., last 1 hour, last 24 hours).
 * Used for ClickHouse/Redis real-time queries.
 */
final readonly class TimeRange
{
    private const string LAST_1_HOUR = '1h';
    private const string LAST_6_HOURS = '6h';
    private const string LAST_12_HOURS = '12h';
    private const string LAST_24_HOURS = '24h';
    private const string LAST_48_HOURS = '48h';

    /**
     * @var array<string, string>
     */
    private const array VALID_RANGES = [
        self::LAST_1_HOUR,
        self::LAST_6_HOURS,
        self::LAST_12_HOURS,
        self::LAST_24_HOURS,
        self::LAST_48_HOURS,
    ];

    public function __construct(
        public readonly string $value,
        public readonly ?CarbonImmutable $from = null,
        public readonly ?CarbonImmutable $to = null
    ) {
        if (!in_array($value, self::VALID_RANGES, true)) {
            throw new \InvalidArgumentException("Invalid time range: {$value}");
        }
    }

    public static function last1Hour(): self
    {
        $now = CarbonImmutable::now();
        $from = $now->subHour();

        return new self(self::LAST_1_HOUR, $from, $now);
    }

    public static function last6Hours(): self
    {
        $now = CarbonImmutable::now();
        $from = $now->subHours(6);

        return new self(self::LAST_6_HOURS, $from, $now);
    }

    public static function last12Hours(): self
    {
        $now = CarbonImmutable::now();
        $from = $now->subHours(12);

        return new self(self::LAST_12_HOURS, $from, $now);
    }

    public static function last24Hours(): self
    {
        $now = CarbonImmutable::now();
        $from = $now->subHours(24);

        return new self(self::LAST_24_HOURS, $from, $now);
    }

    public static function last48Hours(): self
    {
        $now = CarbonImmutable::now();
        $from = $now->subHours(48);

        return new self(self::LAST_48_HOURS, $from, $now);
    }

    public static function custom(CarbonImmutable $from, CarbonImmutable $to): self
    {
        return new self('custom', $from, $to);
    }

    public function from(): CarbonImmutable
    {
        return $this->from ?? CarbonImmutable::now()->subHour();
    }

    public function to(): CarbonImmutable
    {
        return $this->to ?? CarbonImmutable::now();
    }

    public function getHours(): int
    {
        return (int) $this->from()->diffInHours($this->to());
    }

    public function getMinutes(): int
    {
        return (int) $this->from()->diffInMinutes($this->to());
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
