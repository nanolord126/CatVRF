<?php

declare(strict_types=1);

namespace Modules\Loyalty\Application\DTOs;

use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;

final readonly class CalculatePointsDTO
{
    private function __construct(
        public string $programId,
        public int $guestId,
        public CurrencyAmount $orderAmount,
        public ?string $tierSlug,
        public ?array $itemIds,
        public ?string $dayOfWeek,
        public bool $isFirstVisit,
        public bool $isBirthday,
        public ?string $sourceType,
        public ?int $sourceId
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            programId: $data['program_id'],
            guestId: $data['guest_id'],
            orderAmount: CurrencyAmount::fromFloat((float) $data['order_amount']),
            tierSlug: $data['tier_slug'] ?? null,
            itemIds: $data['item_ids'] ?? null,
            dayOfWeek: $data['day_of_week'] ?? null,
            isFirstVisit: $data['is_first_visit'] ?? false,
            isBirthday: $data['is_birthday'] ?? false,
            sourceType: $data['source_type'] ?? null,
            sourceId: $data['source_id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'program_id' => $this->programId,
            'guest_id' => $this->guestId,
            'order_amount' => $this->orderAmount->getValue(),
            'tier_slug' => $this->tierSlug,
            'item_ids' => $this->itemIds,
            'day_of_week' => $this->dayOfWeek,
            'is_first_visit' => $this->isFirstVisit,
            'is_birthday' => $this->isBirthday,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
        ];
    }
}
