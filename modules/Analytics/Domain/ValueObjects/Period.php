<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Period Value Object
 *
 * Represents a time period for analytics queries (e.g., today, last 7 days, custom range).
 * This is a domain value object - immutable and type-safe.
 */
final readonly class Period
{
    private const string TODAY = 'today';
    private const string YESTERDAY = 'yesterday';
    private const string LAST_7_DAYS = 'last_7_days';
    private const string LAST_30_DAYS = 'last_30_days';
    private const string LAST_90_DAYS = 'last_90_days';
    private const string THIS_WEEK = 'this_week';
    private const string LAST_WEEK = 'last_week';
    private const string THIS_MONTH = 'this_month';
    private const string LAST_MONTH = 'last_month';
    private const string THIS_QUARTER = 'this_quarter';
    private const string THIS_YEAR = 'this_year';
    private const string CUSTOM = 'custom';

    /**
     * @var array<string, string>
     */
    private const array VALID_PERIODS = [
        self::TODAY,
        self::YESTERDAY,
        self::LAST_7_DAYS,
        self::LAST_30_DAYS,
        self::LAST_90_DAYS,
        self::THIS_WEEK,
        self::LAST_WEEK,
        self::THIS_MONTH,
        self::LAST_MONTH,
        self::THIS_QUARTER,
        self::THIS_YEAR,
        self::CUSTOM,
    ];

    public function __construct(
        public readonly string $type,
        public readonly ?CarbonImmutable $from = null,
        public readonly ?CarbonImmutable $to = null,
        public readonly ?string $timezone = null
    ) {
        if (!in_array($type, self::VALID_PERIODS, true)) {
            throw new \InvalidArgumentException("Invalid period type: {$type}");
        }

        if ($type === self::CUSTOM && ($from === null || $to === null)) {
            throw new \InvalidArgumentException("Custom period requires from and to dates");
        }

        if ($from !== null && $to !== null && $from->isAfter($to)) {
            throw new \InvalidArgumentException("From date must be before or equal to to date");
        }
    }

    public static function today(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);

        return new self(self::TODAY, $now->startOfDay(), $now->endOfDay(), $tz);
    }

    public static function yesterday(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);
        $yesterday = $now->subDay();

        return new self(self::YESTERDAY, $yesterday->startOfDay(), $yesterday->endOfDay(), $tz);
    }

    public static function last7Days(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);
        $from = $now->subDays(6)->startOfDay();

        return new self(self::LAST_7_DAYS, $from, $now->endOfDay(), $tz);
    }

    public static function last30Days(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);
        $from = $now->subDays(29)->startOfDay();

        return new self(self::LAST_30_DAYS, $from, $now->endOfDay(), $tz);
    }

    public static function last90Days(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);
        $from = $now->subDays(89)->startOfDay();

        return new self(self::LAST_90_DAYS, $from, $now->endOfDay(), $tz);
    }

    public static function thisWeek(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);

        return new self(self::THIS_WEEK, $now->startOfWeek(), $now->endOfWeek(), $tz);
    }

    public static function lastWeek(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);
        $lastWeek = $now->subWeek();

        return new self(self::LAST_WEEK, $lastWeek->startOfWeek(), $lastWeek->endOfWeek(), $tz);
    }

    public static function thisMonth(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);

        return new self(self::THIS_MONTH, $now->startOfMonth(), $now->endOfMonth(), $tz);
    }

    public static function lastMonth(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);
        $lastMonth = $now->subMonth();

        return new self(self::LAST_MONTH, $lastMonth->startOfMonth(), $lastMonth->endOfMonth(), $tz);
    }

    public static function thisQuarter(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);

        return new self(self::THIS_QUARTER, $now->startOfQuarter(), $now->endOfQuarter(), $tz);
    }

    public static function thisYear(?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($tz);

        return new self(self::THIS_YEAR, $now->startOfYear(), $now->endOfYear(), $tz);
    }

    public static function custom(CarbonImmutable $from, CarbonImmutable $to, ?string $timezone = null): self
    {
        $tz = $timezone ?? config('app.timezone');

        return new self(self::CUSTOM, $from->setTimezone($tz), $to->setTimezone($tz), $tz);
    }

    public function from(): CarbonImmutable
    {
        return $this->from ?? CarbonImmutable::now($this->timezone)->startOfDay();
    }

    public function to(): CarbonImmutable
    {
        return $this->to ?? CarbonImmutable::now($this->timezone)->endOfDay();
    }

    public function getPeriod(): CarbonPeriod
    {
        return CarbonPeriod::create($this->from(), $this->to());
    }

    public function getDays(): int
    {
        return $this->from()->diffInDays($this->to()) + 1;
    }

    public function getHours(): int
    {
        return $this->from()->diffInHours($this->to()) + 1;
    }

    /**
     * Get previous period of same duration for comparison (e.g., last 7 days vs previous 7 days).
     */
    public function getPreviousPeriod(): self
    {
        $duration = $this->from()->diffInDays($this->to());
        $prevTo = $this->from()->subDay()->endOfDay();
        $prevFrom = $prevTo->subDays($duration)->startOfDay();

        return new self(self::CUSTOM, $prevFrom, $prevTo, $this->timezone);
    }

    public function isRealtime(): bool
    {
        return $this->type === self::TODAY || $this->type === self::CUSTOM;
    }

    public function __toString(): string
    {
        if ($this->type === self::CUSTOM) {
            return "custom:{$this->from()->toDateString()}:{$this->to()->toDateString()}";
        }

        return $this->type;
    }
}
