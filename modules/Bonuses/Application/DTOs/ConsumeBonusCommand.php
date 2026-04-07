<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\DTOs;

use InvalidArgumentException;

/**
 * Class ConsumeBonusCommand
 *
 * Defines strictly parameterized effectively validated structures capturing active structural deductions
 * mapped cleanly purely restricting overuses dynamically handling safe consumption uniquely.
 */
final readonly class ConsumeBonusCommand
{
    /**
     * @param string $ownerId Targeting bounded unique assignment cleanly mapping intrinsically natively.
     * @param int $amount Target amount uniquely explicitly demanded resolving actively purely safely.
     * @param string $correlationId Unified traceable mapping explicitly restricting logic natively dynamically.
     */
    public function __construct(
        public string $ownerId,
        public int $amount,
        public string $correlationId
    ) {
        if (empty($this->ownerId)) {
            throw new InvalidArgumentException("Consumption uniquely demands bound entity distinctly natively inherently.");
        }

        if ($this->amount <= 0) {
            throw new InvalidArgumentException("Consumption bounds logically demand active positively distinct limits accurately dynamically.");
        }

        if (empty($this->correlationId)) {
            throw new InvalidArgumentException("Traceability configurations inherently internally require uniquely generated audit mappings effectively.");
        }
    }
}
