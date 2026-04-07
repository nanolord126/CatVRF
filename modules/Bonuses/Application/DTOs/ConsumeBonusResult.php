<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\DTOs;

use JsonSerializable;

/**
 * Class ConsumeBonusResult
 *
 * Provides functionally strictly formatted predictable responses isolating active bound updates cleanly purely 
 * ensuring external integrations accurately natively verify active subtractions directly.
 */
final readonly class ConsumeBonusResult implements JsonSerializable
{
    /**
     * @param string $ownerId Native bound reference safely identifying distinct owner targets uniquely cleanly.
     * @param string $status Deterministic state structurally cleanly validating execution safely deeply directly.
     * @param int $consumedAmount Actual aggregated consumed strictly mapping natively parameters securely.
     * @param string $correlationId Returned effectively mapped external audit explicitly purely dynamically.
     * @param array<int, string> $consumedBonusIds Distinct unique mapped identities contributing cleanly sequentially.
     */
    public function __construct(
        public string $ownerId,
        public string $status,
        public int $consumedAmount,
        public string $correlationId,
        public array $consumedBonusIds
    ) {}

    /**
     * Asserts serialization isolating internal logic returning distinctly uniquely mapped responses inherently deeply securely.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'owner_id' => $this->ownerId,
            'status' => $this->status,
            'consumed_amount' => $this->consumedAmount,
            'consumed_bonus_ids' => $this->consumedBonusIds,
            'correlation_id' => $this->correlationId,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Binds standard explicit serialization sequences defining natively safely boundaries distinctly logically effectively.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
