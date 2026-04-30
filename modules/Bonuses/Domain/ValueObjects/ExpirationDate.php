<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Bonuses\Domain\Enums\BonusType;

/**
 * Value Object ExpirationDate
 *
 * Encapsulates bonus expiration logic with validation and business rules.
 * Handles default expiration periods based on bonus type and validates temporal bounds.
 * Provides methods for calculating remaining time and checking expiration status.
 */
final readonly class ExpirationDate
{
    /**
     * @param  DateTimeImmutable  $expiresAt  The exact timestamp when the bonus expires.
     * @param  DateTimeImmutable  $issuedAt  The timestamp when the bonus was issued.
     */
    public function __construct(
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $issuedAt
    ) {
        $this->validate();
    }

    /**
     * Validates expiration date constraints.
     */
    private function validate(): void
    {
        if ($this->expiresAt <= $this->issuedAt) {
            throw new InvalidArgumentException('Expiration date must be after issue date');
        }

        $maxExpirationYears = 5;
        $maxExpirationDate = $this->issuedAt->modify("+{$maxExpirationYears} years");

        if ($this->expiresAt > $maxExpirationDate) {
            throw new InvalidArgumentException(
                sprintf('Expiration date cannot be more than %d years from issue date', $maxExpirationYears)
            );
        }
    }

    /**
     * Creates an ExpirationDate with default period based on bonus type.
     */
    public static function fromBonusType(BonusType $type, DateTimeImmutable $issuedAt = new DateTimeImmutable()): self
    {
        $defaultDays = $type->getDefaultExpirationDays();

        if ($defaultDays === null) {
            throw new InvalidArgumentException(
                sprintf('Bonus type %s does not have a default expiration period', $type->value)
            );
        }

        $expiresAt = $issuedAt->modify("+{$defaultDays} days");

        return new self($expiresAt, $issuedAt);
    }

    /**
     * Creates an ExpirationDate with a custom number of days from now.
     */
    public static function fromDays(int $days, DateTimeImmutable $issuedAt = new DateTimeImmutable()): self
    {
        if ($days < 1) {
            throw new InvalidArgumentException('Expiration days must be at least 1');
        }

        if ($days > 1825) { // 5 years
            throw new InvalidArgumentException('Expiration days cannot exceed 5 years (1825 days)');
        }

        $expiresAt = $issuedAt->modify("+{$days} days");

        return new self($expiresAt, $issuedAt);
    }

    /**
     * Creates an ExpirationDate from a specific datetime string.
     */
    public static function fromString(string $expiresAt, DateTimeImmutable $issuedAt = new DateTimeImmutable()): self
    {
        $expiresDateTime = new DateTimeImmutable($expiresAt);

        return new self($expiresDateTime, $issuedAt);
    }

    /**
     * Creates an ExpirationDate that never expires (far future date).
     * Used for permanent bonuses that should not expire.
     */
    public static function never(DateTimeImmutable $issuedAt = new DateTimeImmutable()): self
    {
        $expiresAt = $issuedAt->modify('+100 years');

        return new self($expiresAt, $issuedAt);
    }

    /**
     * Checks if the bonus has expired as of the given datetime.
     */
    public function isExpired(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        return $now >= $this->expiresAt;
    }

    /**
     * Checks if the bonus will expire within the given number of days.
     */
    public function expiresWithinDays(int $days, DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        if ($this->isExpired($now)) {
            return false;
        }

        $threshold = $now->modify("+{$days} days");

        return $this->expiresAt <= $threshold;
    }

    /**
     * Calculates the number of days remaining until expiration.
     * Returns 0 if already expired.
     */
    public function getDaysRemaining(DateTimeImmutable $now = new DateTimeImmutable()): int
    {
        if ($this->isExpired($now)) {
            return 0;
        }

        $interval = $now->diff($this->expiresAt);

        return $interval->days;
    }

    /**
     * Calculates the number of hours remaining until expiration.
     * Returns 0 if already expired.
     */
    public function getHoursRemaining(DateTimeImmutable $now = new DateTimeImmutable()): int
    {
        if ($this->isExpired($now)) {
            return 0;
        }

        $interval = $now->diff($this->expiresAt);

        return ($interval->days * 24) + $interval->h;
    }

    /**
     * Calculates the percentage of time elapsed since issuance.
     * Returns 100 if expired.
     */
    public function getTimeElapsedPercentage(DateTimeImmutable $now = new DateTimeImmutable()): float
    {
        if ($this->isExpired($now)) {
            return 100.0;
        }

        $totalDuration = $this->expiresAt->getTimestamp() - $this->issuedAt->getTimestamp();
        $elapsedDuration = $now->getTimestamp() - $this->issuedAt->getTimestamp();

        if ($totalDuration <= 0) {
            return 100.0;
        }

        return min(100.0, ($elapsedDuration / $totalDuration) * 100);
    }

    /**
     * Calculates the percentage of time remaining until expiration.
     * Returns 0 if expired.
     */
    public function getTimeRemainingPercentage(DateTimeImmutable $now = new DateTimeImmutable()): float
    {
        return max(0.0, 100.0 - $this->getTimeElapsedPercentage($now));
    }

    /**
     * Determines if this is a short-lived bonus (expires within 30 days).
     */
    public function isShortLived(): bool
    {
        $totalDuration = $this->expiresAt->getTimestamp() - $this->issuedAt->getTimestamp();
        $thirtyDaysInSeconds = 30 * 24 * 60 * 60;

        return $totalDuration <= $thirtyDaysInSeconds;
    }

    /**
     * Determines if this is a long-term bonus (expires after 6 months).
     */
    public function isLongTerm(): bool
    {
        $totalDuration = $this->expiresAt->getTimestamp() - $this->issuedAt->getTimestamp();
        $sixMonthsInSeconds = 180 * 24 * 60 * 60;

        return $totalDuration > $sixMonthsInSeconds;
    }

    /**
     * Returns the total duration in days between issue and expiration.
     */
    public function getTotalDurationDays(): int
    {
        $interval = $this->issuedAt->diff($this->expiresAt);

        return $interval->days;
    }

    /**
     * Formats the expiration date for display.
     */
    public function format(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->expiresAt->format($format);
    }

    /**
     * Formats the remaining time as a human-readable string.
     */
    public function formatRemainingTime(DateTimeImmutable $now = new DateTimeImmutable()): string
    {
        if ($this->isExpired($now)) {
            return 'Expired';
        }

        $days = $this->getDaysRemaining($now);

        if ($days === 0) {
            $hours = $this->getHoursRemaining($now);
            return "{$hours} hours remaining";
        }

        if ($days === 1) {
            return '1 day remaining';
        }

        if ($days < 7) {
            return "{$days} days remaining";
        }

        if ($days < 30) {
            $weeks = floor($days / 7);
            return "{$weeks} weeks remaining";
        }

        if ($days < 365) {
            $months = floor($days / 30);
            return "{$months} months remaining";
        }

        $years = floor($days / 365);
        return "{$years} years remaining";
    }

    /**
     * Converts to array for persistence.
     */
    public function toArray(): array
    {
        return [
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'issued_at' => $this->issuedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Creates from array for reconstruction from persistence.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            expiresAt: new DateTimeImmutable($data['expires_at']),
            issuedAt: new DateTimeImmutable($data['issued_at']),
        );
    }
}
