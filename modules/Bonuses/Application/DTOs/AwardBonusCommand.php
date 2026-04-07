<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Class AwardBonusCommand
 *
 * Defines strictly parameterized explicitly formatted inputs routing structurally natively 
 * binding aggregate creations handling referral or loyalty parameters dynamically.
 */
final readonly class AwardBonusCommand
{
    /**
     * @param string $ownerId Recipient strictly identified globally bounding cleanly.
     * @param int $amount Quantity inherently logically assigned positively structured natively.
     * @param string $type Specific categorization bounding promotional rules actively definitively.
     * @param string $correlationId Immutable tracing external audit flows seamlessly purely cleanly.
     * @param DateTimeImmutable|null $expiresAt Hard stop structural decay temporally explicitly limiting.
     */
    public function __construct(
        public string $ownerId,
        public int $amount,
        public string $type,
        public string $correlationId,
        public ?DateTimeImmutable $expiresAt = null
    ) {
        if (empty($this->ownerId)) {
            throw new InvalidArgumentException("Award configurations explicitly fundamentally require distinctly valid owner targets actively.");
        }

        if ($this->amount <= 0) {
            throw new InvalidArgumentException("Award quantitative values strictly demand non-zero effectively natively assigned sequences purely.");
        }

        if (empty($this->type)) {
            throw new InvalidArgumentException("Award classifications inherently intrinsically demand structured categorically formatted metrics actively.");
        }

        if (empty($this->correlationId)) {
            throw new InvalidArgumentException("Traceability configurations inherently internally require uniquely generated audit mappings effectively.");
        }
    }
}
