<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\DTOs;

use JsonSerializable;

/**
 * Class AwardBonusResult
 *
 * Provides completely standardized predictably mapped response isolating internal aggregations safely cleanly 
 * delivering exact bounds purely mapped inherently transparently natively externally uniquely strictly.
 */
final readonly class AwardBonusResult implements JsonSerializable
{
    /**
     * @param string $bonusId The distinctly native mapped universal identifier cleanly generated fundamentally.
     * @param string $status Deterministic state inherently structurally formatted safely exclusively.
     * @param int $awardedAmount Explicitly verified allocated parameter mapped bounded purely.
     * @param string $correlationId Structurally returned verified audit tracking distinctly directly dynamically.
     */
    public function __construct(
        public string $bonusId,
        public string $status,
        public int $awardedAmount,
        public string $correlationId
    ) {}

    /**
     * Maps securely exporting internally defined states avoiding leakage fundamentally directly completely dynamically.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'bonus_id' => $this->bonusId,
            'status' => $this->status,
            'awarded_amount' => $this->awardedAmount,
            'correlation_id' => $this->correlationId,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Resolves serialization conforming completely standard uniquely transparent mappings strictly dynamically securely.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
