<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Represents the area of a property in square metres.
 */
final readonly class Area
{
    public function __construct(
        private float $squareMeters) {
        if ($squareMeters <= 0.0) {
            throw new InvalidArgumentException(
                "Area must be positive, got {$squareMeters} m²."
            );
        }
    }

    public static function fromFloat(float $squareMeters): self
    {
        return new self($squareMeters);
    }

    public function getSquareMeters(): float
    {
        return $this->squareMeters;
    }

    /**
     * Returns the price per square metre in kopecks.
     */
    public function pricePerSquareMeter(Price $totalPrice): int
    {
        return (int) round($totalPrice->getAmountKopecks() / $this->squareMeters);
    }

    /**
     * Returns total price from a per-m² rate (in kopecks).
     */
    public function totalPriceFromRate(int $rateKopecksPerSqm): Price
    {
        $total = (int) round($rateKopecksPerSqm * $this->squareMeters);

        return Price::fromKopecks($total);
    }

    public function equals(self $other): bool
    {
        return abs($this->squareMeters - $other->squareMeters) < 0.01;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->squareMeters > $other->squareMeters;
    }

    public function format(): string
    {
        return number_format($this->squareMeters, 1) . ' м²';
    }
}
