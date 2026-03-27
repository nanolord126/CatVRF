<?php

declare(strict_types=1);

namespace App\Domains\Tickets\DTO;

/**
 * КАНОН 2026: DTO для покупки билета.
 * Слой 4: DTO.
 */
final readonly class BuyTicketDto
{
    public function __construct(
        public int $eventId,
        public int $ticketTypeId,
        public int $userId,
        public int $quantity = 1,
        public ?string $sector = null,
        public ?int $row = null,
        public ?int $number = null,
        public string $correlation_id = '',
        public array $metadata = []
    ) {}

    /**
     * Создание из массива данных.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            eventId: (int) ($data['event_id'] ?? 0),
            ticketTypeId: (int) ($data['ticket_type_id'] ?? 0),
            userId: (int) ($data['user_id'] ?? 0),
            quantity: (int) ($data['quantity'] ?? 1),
            sector: $data['sector'] ?? null,
            row: isset($data['row']) ? (int) $data['row'] : null,
            number: isset($data['number']) ? (int) $data['number'] : null,
            correlation_id: $data['correlation_id'] ?? '',
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Экспорт в массив.
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'ticket_type_id' => $this->ticketTypeId,
            'user_id' => $this->userId,
            'quantity' => $this->quantity,
            'sector' => $this->sector,
            'row' => $this->row,
            'number' => $this->number,
            'correlation_id' => $this->correlation_id,
            'metadata' => $this->metadata,
        ];
    }
}
