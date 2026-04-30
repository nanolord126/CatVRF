<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class SKU
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 50;

    public function __construct(public string $value)
    {
        $normalized = trim(strtoupper($value));

        if (strlen($normalized) < self::MIN_LENGTH) {
            throw new InvalidArgumentException('SKU must be at least ' . self::MIN_LENGTH . ' characters');
        }

        if (strlen($normalized) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('SKU must not exceed ' . self::MAX_LENGTH . ' characters');
        }

        if (!preg_match('/^[A-Z0-9\-_]+$/', $normalized)) {
            throw new InvalidArgumentException('SKU can only contain letters, numbers, hyphens and underscores');
        }

        $this->value = $normalized;
    }

    public static function fromString(string $sku): self
    {
        return new self($sku);
    }

    public static function generate(string $prefix = ''): self
    {
        $timestamp = (string) time();
        $random = substr(md5(uniqid('', true)), 0, 6);
        $value = $prefix . $timestamp . $random;

        return new self($value);
    }

    public function equals(SKU $other): bool
    {
        return $this->value === $other->value;
    }

    public function startsWith(string $prefix): bool
    {
        return str_starts_with($this->value, strtoupper($prefix));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
