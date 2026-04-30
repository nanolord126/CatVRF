<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Timestamp Value Object
 *
 * Represents a timestamp as a value object following Domain-Driven Design principles.
 * Provides a type-safe, immutable wrapper around CarbonImmutable for date/time operations.
 *
 * @package Modules\Analytics\Domain\ValueObjects
 */
final readonly class Timestamp
{
    /**
     * Create a new Timestamp instance.
     *
     * @param CarbonImmutable $value The underlying CarbonImmutable value
     */
    public function __construct(
        public CarbonImmutable $value,
    ) {
        $this->validate();
    }

    /**
     * Create a Timestamp representing the current time.
     *
     * @return self
     */
    public static function now(): self
    {
        return new self(CarbonImmutable::now());
    }

    /**
     * Create a Timestamp from a datetime string.
     *
     * @param string $datetime The datetime string to parse
     * @return self
     * @throws \InvalidArgumentException If the string cannot be parsed
     */
    public static function fromString(string $datetime): self
    {
        try {
            return new self(CarbonImmutable::parse($datetime));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf('Unable to parse datetime string: %s', $datetime),
                0,
                $e
            );
        }
    }

    /**
     * Create a Timestamp from a Unix timestamp.
     *
     * @param int $timestamp The Unix timestamp
     * @return self
     */
    public static function fromTimestamp(int $timestamp): self
    {
        return new self(CarbonImmutable::createFromTimestamp($timestamp));
    }

    /**
     * Create a Timestamp from a DateTimeInterface object.
     *
     * @param DateTimeInterface $datetime The DateTimeInterface object
     * @return self
     */
    public static function fromDateTime(DateTimeInterface $datetime): self
    {
        return new self(CarbonImmutable::instance($datetime));
    }

    /**
     * Create a Timestamp representing the start of today.
     *
     * @return self
     */
    public static function today(): self
    {
        return new self(CarbonImmutable::now()->startOfDay());
    }

    /**
     * Create a Timestamp representing the start of tomorrow.
     *
     * @return self
     */
    public static function tomorrow(): self
    {
        return new self(CarbonImmutable::now()->addDay()->startOfDay());
    }

    /**
     * Create a Timestamp representing the start of yesterday.
     *
     * @return self
     */
    public static function yesterday(): self
    {
        return new self(CarbonImmutable::now()->subDay()->startOfDay());
    }

    /**
     * Validate the timestamp value.
     *
     * @return void
     */
    private function validate(): void
    {
        // CarbonImmutable handles most validation internally
        // Additional validation can be added here if needed
    }

    /**
     * Convert to ISO 8601 string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value->toIso8601String();
    }

    /**
     * Check if this Timestamp equals another Timestamp.
     *
     * @param self $other The other Timestamp to compare with
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value->equalTo($other->value);
    }

    /**
     * Check if this Timestamp is before another Timestamp.
     *
     * @param self $other The other Timestamp to compare with
     * @return bool
     */
    public function isBefore(self $other): bool
    {
        return $this->value->lessThan($other->value);
    }

    /**
     * Check if this Timestamp is after another Timestamp.
     *
     * @param self $other The other Timestamp to compare with
     * @return bool
     */
    public function isAfter(self $other): bool
    {
        return $this->value->greaterThan($other->value);
    }

    /**
     * Check if this Timestamp is between two other Timestamps.
     *
     * @param self $from The start Timestamp
     * @param self $to The end Timestamp
     * @return bool
     */
    public function isBetween(self $from, self $to): bool
    {
        return $this->value->between($from->value, $to->value);
    }

    /**
     * Get the difference in seconds from another Timestamp.
     *
     * @param self $other The other Timestamp
     * @return int
     */
    public function diffInSeconds(self $other): int
    {
        return (int) $this->value->diffInSeconds($other->value);
    }

    /**
     * Get the difference in days from another Timestamp.
     *
     * @param self $other The other Timestamp
     * @return int
     */
    public function diffInDays(self $other): int
    {
        return (int) $this->value->diffInDays($other->value);
    }

    /**
     * Get the difference in hours from another Timestamp.
     *
     * @param self $other The other Timestamp
     * @return int
     */
    public function diffInHours(self $other): int
    {
        return (int) $this->value->diffInHours($other->value);
    }

    /**
     * Add a number of days to this Timestamp.
     *
     * @param int $days Number of days to add
     * @return self
     */
    public function addDays(int $days): self
    {
        return new self($this->value->addDays($days));
    }

    /**
     * Subtract a number of days from this Timestamp.
     *
     * @param int $days Number of days to subtract
     * @return self
     */
    public function subDays(int $days): self
    {
        return new self($this->value->subDays($days));
    }

    /**
     * Add a number of hours to this Timestamp.
     *
     * @param int $hours Number of hours to add
     * @return self
     */
    public function addHours(int $hours): self
    {
        return new self($this->value->addHours($hours));
    }

    /**
     * Subtract a number of hours from this Timestamp.
     *
     * @param int $hours Number of hours to subtract
     * @return self
     */
    public function subHours(int $hours): self
    {
        return new self($this->value->subHours($hours));
    }

    /**
     * Get the start of the day for this Timestamp.
     *
     * @return self
     */
    public function startOfDay(): self
    {
        return new self($this->value->startOfDay());
    }

    /**
     * Get the end of the day for this Timestamp.
     *
     * @return self
     */
    public function endOfDay(): self
    {
        return new self($this->value->endOfDay());
    }

    /**
     * Get the start of the month for this Timestamp.
     *
     * @return self
     */
    public function startOfMonth(): self
    {
        return new self($this->value->startOfMonth());
    }

    /**
     * Get the end of the month for this Timestamp.
     *
     * @return self
     */
    public function endOfMonth(): self
    {
        return new self($this->value->endOfMonth());
    }

    /**
     * Get the Unix timestamp value.
     *
     * @return int
     */
    public function toTimestamp(): int
    {
        return $this->value->timestamp;
    }

    /**
     * Format the timestamp according to a format string.
     *
     * @param string $format The format string
     * @return string
     */
    public function format(string $format): string
    {
        return $this->value->format($format);
    }

    /**
     * Check if this Timestamp is in the future.
     *
     * @return bool
     */
    public function isFuture(): bool
    {
        return $this->value->isFuture();
    }

    /**
     * Check if this Timestamp is in the past.
     *
     * @return bool
     */
    public function isPast(): bool
    {
        return $this->value->isPast();
    }

    /**
     * Check if this Timestamp is today.
     *
     * @return bool
     */
    public function isToday(): bool
    {
        return $this->value->isToday();
    }

    /**
     * Validate if a given string is a valid datetime.
     *
     * @param string $datetime The datetime string to validate
     * @return bool
     */
    public static function isValid(string $datetime): bool
    {
        try {
            CarbonImmutable::parse($datetime);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
