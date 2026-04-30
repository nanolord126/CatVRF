<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObjects;

/**
 * User ID Value Object
 *
 * Represents a user identifier as a value object following Domain-Driven Design principles.
 * Value objects are immutable objects that are defined by their attributes rather than identity.
 * This ensures that two UserId objects with the same value are considered equal.
 *
 * @package Modules\Analytics\Domain\ValueObjects
 */
final readonly class UserId
{
    /**
     * Create a new UserId instance.
     *
     * @param int $value The user ID value
     * @throws \InvalidArgumentException If the value is not a positive integer
     */
    public function __construct(
        public int $value,
    ) {
        $this->validate();
    }

    /**
     * Create a UserId from an integer value.
     *
     * @param int $value The user ID value
     * @return self
     * @throws \InvalidArgumentException If the value is not a positive integer
     */
    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    /**
     * Create a UserId from a string value.
     *
     * @param string $value The user ID as a string
     * @return self
     * @throws \InvalidArgumentException If the value is not a valid integer
     */
    public static function fromString(string $value): self
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($intValue === false) {
            throw new \InvalidArgumentException('User ID must be a valid integer');
        }

        return new self($intValue);
    }

    /**
     * Validate the user ID value.
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    private function validate(): void
    {
        if ($this->value <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }
    }

    /**
     * Convert to string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Check if this UserId equals another UserId.
     *
     * @param self $other The other UserId to compare with
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the integer value.
     *
     * @return int
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * Check if the user ID is a system user (ID 1 or similar).
     *
     * @return bool
     */
    public function isSystemUser(): bool
    {
        return $this->value === 1;
    }

    /**
     * Check if the user ID is in a specific range.
     *
     * @param int $min Minimum value (inclusive)
     * @param int $max Maximum value (inclusive)
     * @return bool
     */
    public function isInRange(int $min, int $max): bool
    {
        return $this->value >= $min && $this->value <= $max;
    }

    /**
     * Create a new UserId with a different value.
     *
     * @param int $newValue The new value
     * @return self
     */
    public function withValue(int $newValue): self
    {
        return new self($newValue);
    }

    /**
     * Check if this UserId is greater than another.
     *
     * @param self $other
     * @return bool
     */
    public function isGreaterThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Check if this UserId is less than another.
     *
     * @param self $other
     * @return bool
     */
    public function isLessThan(self $other): bool
    {
        return $this->value < $other->value;
    }

    /**
     * Get a hash of the user ID for caching purposes.
     *
     * @return string
     */
    public function hash(): string
    {
        return md5((string) $this->value);
    }

    /**
     * Validate if a given value is a valid user ID.
     *
     * @param mixed $value The value to validate
     * @return bool
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_int($value) && !is_string($value)) {
            return false;
        }

        if (is_string($value)) {
            $value = filter_var($value, FILTER_VALIDATE_INT);
            if ($value === false) {
                return false;
            }
        }

        return $value > 0;
    }
}
