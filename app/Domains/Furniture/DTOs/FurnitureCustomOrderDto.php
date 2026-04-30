<?php

declare(strict_types=1);

namespace App\Domains\Furniture\DTOs;

final readonly class FurnitureCustomOrderDto
{
    public function __construct(
        public int $userId,
        public int $roomTypeId,
        public int $totalAmount,
        public array $aiSpecification,
        public array $photoAnalysis = [],
        public bool $includeAssembly = true,
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'room_type_id' => $this->roomTypeId,
            'total_amount' => $this->totalAmount,
            'ai_specification' => $this->aiSpecification,
            'photo_analysis' => $this->photoAnalysis,
            'include_assembly' => $this->includeAssembly,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            roomTypeId: $data['room_type_id'],
            totalAmount: $data['total_amount'],
            aiSpecification: $data['ai_specification'],
            photoAnalysis: $data['photo_analysis'] ?? [],
            includeAssembly: $data['include_assembly'] ?? true,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
