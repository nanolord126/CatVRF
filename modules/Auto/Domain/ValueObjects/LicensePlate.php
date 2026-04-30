<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\ValueObjects;

final readonly class LicensePlate
{
    public function __construct(
        public string $value,
    ) {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $value));
        
        if (empty($normalized)) {
            throw new \InvalidArgumentException('License plate cannot be empty');
        }

        if (strlen($normalized) < 5 || strlen($normalized) > 10) {
            throw new \InvalidArgumentException('License plate must be between 5 and 10 characters');
        }

        $this->value = $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function format(): string
    {
        // Format license plate with spaces for readability
        return implode(' ', str_split($this->value, 3));
    }
}
