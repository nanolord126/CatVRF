<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\ValueObjects;

use InvalidArgumentException;
use Illuminate\Support\Str;

/**
 * Value Object CorrelationId
 *
 * Encapsulates correlation ID generation and validation for distributed tracing.
 * Ensures all bonus operations can be traced across services and systems.
 * Provides methods for generating, validating, and formatting correlation IDs.
 */
final readonly class CorrelationId
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 255;
    private const PREFIX_SEPARATOR = ':';

    /**
     * @param  string  $value  The correlation ID value.
     * @param  string|null  $prefix  Optional prefix for categorization (e.g., 'bonus', 'refund').
     */
    public function __construct(
        public string $value,
        public ?string $prefix = null
    ) {
        $this->validate();
    }

    /**
     * Validates the correlation ID constraints.
     */
    private function validate(): void
    {
        $idValue = $this->getFullId();

        if (empty($idValue)) {
            throw new InvalidArgumentException('Correlation ID cannot be empty');
        }

        $length = strlen($idValue);

        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Correlation ID must be at least %d characters long', self::MIN_LENGTH)
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Correlation ID cannot exceed %d characters', self::MAX_LENGTH)
            );
        }

        // Validate that the ID contains only safe characters
        if (!preg_match('/^[a-zA-Z0-9\-_:\.]+$/', $idValue)) {
            throw new InvalidArgumentException(
                'Correlation ID can only contain alphanumeric characters, hyphens, underscores, colons, and dots'
            );
        }
    }

    /**
     * Generates a new random correlation ID.
     */
    public static function generate(?string $prefix = null): self
    {
        $uuid = Str::uuid()->toString();

        return new self($uuid, $prefix);
    }

    /**
     * Generates a correlation ID with a specific format.
     */
    public static function generateFormatted(string $format = 'bonus_{timestamp}_{random}'): self
    {
        $timestamp = time();
        $random = Str::random(8);

        $value = str_replace(
            ['{timestamp}', '{random}'],
            [$timestamp, $random],
            $format
        );

        return new self($value);
    }

    /**
     * Creates a correlation ID from an existing string value.
     */
    public static function fromString(string $value, ?string $prefix = null): self
    {
        return new self($value, $prefix);
    }

    /**
     * Creates a correlation ID from a request header or similar source.
     * Falls back to generating a new ID if none is provided.
     */
    public static function fromRequestOrGenerate(?string $value, ?string $prefix = null): self
    {
        if (!empty($value)) {
            return new self($value, $prefix);
        }

        return self::generate($prefix);
    }

    /**
     * Returns the full correlation ID with prefix if set.
     */
    public function getFullId(): string
    {
        if ($this->prefix === null) {
            return $this->value;
        }

        return $this->prefix . self::PREFIX_SEPARATOR . $this->value;
    }

    /**
     * Returns the correlation ID without the prefix.
     */
    public function getValueWithoutPrefix(): string
    {
        return $this->value;
    }

    /**
     * Checks if this correlation ID has a prefix.
     */
    public function hasPrefix(): bool
    {
        return $this->prefix !== null;
    }

    /**
     * Checks if the correlation ID matches the given pattern.
     */
    public function matchesPattern(string $pattern): bool
    {
        return preg_match($pattern, $this->getFullId()) === 1;
    }

    /**
     * Checks if this correlation ID starts with the given string.
     */
    public function startsWith(string $prefix): bool
    {
        return str_starts_with($this->getFullId(), $prefix);
    }

    /**
     * Creates a child correlation ID for nested operations.
     * Appends a suffix to maintain traceability hierarchy.
     */
    public function createChild(string $suffix): self
    {
        $childValue = $this->getFullId() . '.' . $suffix;

        return new self($childValue, $this->prefix);
    }

    /**
     * Extracts the parent correlation ID if this is a child ID.
     */
    public function getParent(): ?self
    {
        $lastDot = strrpos($this->getFullId(), '.');

        if ($lastDot === false) {
            return null;
        }

        $parentValue = substr($this->getFullId(), 0, $lastDot);

        return new self($parentValue, $this->prefix);
    }

    /**
     * Checks if this is a child correlation ID (contains a dot).
     */
    public function isChild(): bool
    {
        return str_contains($this->getFullId(), '.');
    }

    /**
     * Returns the depth of the correlation ID hierarchy.
     * Root IDs have depth 0, first-level children have depth 1, etc.
     */
    public function getDepth(): int
    {
        return substr_count($this->getFullId(), '.');
    }

    /**
     * Converts to string for logging and storage.
     */
    public function toString(): string
    {
        return $this->getFullId();
    }

    /**
     * Converts to array for persistence.
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'prefix' => $this->prefix,
            'full_id' => $this->getFullId(),
        ];
    }

    /**
     * Creates from array for reconstruction from persistence.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: $data['value'],
            prefix: $data['prefix'] ?? null,
        );
    }

    /**
     * String representation for implicit casting.
     */
    public function __toString(): string
    {
        return $this->getFullId();
    }
}
