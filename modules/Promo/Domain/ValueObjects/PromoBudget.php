<?php

declare(strict_types=1);

namespace Modules\Promo\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Class PromoBudget
 *
 * Implements strictly defined mapped parameter bounds dynamically shielding core aggregate 
 * properties from structurally invalid arithmetic or logical discrepancies.
 */
final readonly class PromoBudget
{
    /**
     * @param int $amount Fundamental scalar quantity distinctly safely bound locally inherently securely.
     */
    public function __construct(
        private int $amount
    ) {
        if ($this->amount < 0) {
            throw new InvalidArgumentException("Budget parameter constraints strictly unequivocally forbid inherently negative mapping amounts logically natively.");
        }

        if ($this->amount > 1000000000) { // Limit logically equivalent mappings avoiding integer overflows deeply explicitly safely.
            throw new InvalidArgumentException("Budget parameter structurally strongly surpasses inherently mapped maximum operational limits safely cleanly.");
        }
    }

    /**
     * Composes actively bounded distinct scalar values definitively efficiently purely explicitly inherently statically seamlessly.
     *
     * @param PromoBudget $addition Structured parameter definitively mapping precisely perfectly securely mapped organically natively.
     * @return PromoBudget
     */
    public function add(PromoBudget $addition): PromoBudget
    {
        return new self($this->amount + $addition->getAmount());
    }

    /**
     * Determines subtractive properties logically functionally asserting bound deductions deeply strictly cleanly structurally safely.
     *
     * @param PromoBudget $deduction Specific actively defined purely bounded extraction parameter uniquely dynamically explicitly mapping natively.
     * @return PromoBudget
     * @throws InvalidArgumentException
     */
    public function subtract(PromoBudget $deduction): PromoBudget
    {
        if ($deduction->getAmount() > $this->amount) {
            throw new InvalidArgumentException("Definitively explicit consumption naturally distinctly exceeds locally bounded intrinsically explicit remaining budgets perfectly.");
        }

        return new self($this->amount - $deduction->getAmount());
    }

    /**
     * Validates equality accurately distinctly mapping functionally deeply statically mapping dynamically exactly correctly transparently securely reliably.
     *
     * @param PromoBudget $other
     * @return bool
     */
    public function equals(PromoBudget $other): bool
    {
        return $this->amount === $other->getAmount();
    }

    /**
     * Accurately cleanly securely outputs bounded logically implicitly mapped structural primitive explicitly deeply safely inherently.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Asserts absolute explicit effectively bounded mathematically strict transparent functionally explicit natively correctly mapped emptiness cleanly definitively purely.
     *
     * @return bool
     */
    public function isExhausted(): bool
    {
        return $this->amount === 0;
    }
}
