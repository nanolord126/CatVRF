<?php declare(strict_types=1);

namespace App\Domains\Tickets\DTO;

/**
 * Class BuyTicketDto
 *
 * Part of the Tickets vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Tickets\DTO
 */
final readonly class BuyTicketDto
{

    public function __construct(
            public int $eventId,
            public int $ticketTypeId,
            public int $userId,
            private int $quantity = 1,
            private ?string $sector = null,
            private readonly ?int $row = null,
            private readonly ?int $number = null,
            private string $correlation_id = '',
            private array $metadata = []
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
