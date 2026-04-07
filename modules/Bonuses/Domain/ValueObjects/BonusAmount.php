<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Class BonusAmount
 *
 * Value Object explicitly separating standard real-world Money sequences
 * from internal Loyalty representations. Bonuses often map to kopecks/cents functionally,
 * but have explicit usage restrictions, expiration decay models, and cannot natively
 * convert to pure money without business logic transformations.
 */
final readonly class BonusAmount
{
    /**
     * @param int $amount The intrinsic primitive scalar quantity.
     */
    public function __construct(
        private int $amount
    ) {
        if ($this->amount < 0) {
            throw new InvalidArgumentException("A bonus amount mapping fundamentally requires non-negative baseline quantities.");
        }
        
        // Logical ceiling ensuring integer bounds aren't trivially overflowed 
        // leading to massive accidental loyalty payouts.
        if ($this->amount > 1000000000) {
            throw new InvalidArgumentException("Proposed bonus quantity massively exceeds isolated theoretical boundaries.");
        }
    }

    /**
     * Compares equality safely handling intrinsic internal primitive sequences.
     *
     * @param BonusAmount $other Counterpart value bounding box sequence.
     * @return bool
     */
    public function equals(BonusAmount $other): bool
    {
        return $this->amount === $other->getAmount();
    }

    /**
     * Merges current absolute quantity combining structurally verified external inputs safely.
     *
     * @param BonusAmount $addition
     * @return BonusAmount
     */
    public function add(BonusAmount $addition): BonusAmount
    {
        return new self($this->amount + $addition->getAmount());
    }

    /**
     * Deducts intrinsic structures rejecting implicitly states traversing below absolute zeroes trivially.
     *
     * @param BonusAmount $deduction
     * @return BonusAmount
     * @throws InvalidArgumentException
     */
    public function subtract(BonusAmount $deduction): BonusAmount
    {
        if ($deduction->getAmount() > $this->amount) {
            throw new InvalidArgumentException("Deduction logically surpasses current intrinsic bounds.");
        }

        return new self($this->amount - $deduction->getAmount());
    }

    /**
     * Checks if the value resolves absolutely effectively to zero mapping directly.
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    /**
     * Extracts underlying primitive isolating scalar transitions into integrations.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }
}
