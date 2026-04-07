<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\ValueObjects;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Stringable;

final readonly class AppointmentId implements Stringable
{
    /**
     * @param string $value
     */
    private function __construct(private string $value)
    {
        $this->ensureIsValidUuid($value);
    }

    /**
     * @param string $value
     * @return void
     * @throws InvalidArgumentException
     */
    private function ensureIsValidUuid(string $value): void
    {
        if (!Str::isUuid($value)) {
            throw new InvalidArgumentException(
                sprintf('<%s> does not allow the value <%s>.', static::class, $value)
            );
        }
    }

    /**
     * @param string $value
     * @return static
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * @return static
     */
    public static function generate(): self
    {
        return new self(Str::uuid()->toString());
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
