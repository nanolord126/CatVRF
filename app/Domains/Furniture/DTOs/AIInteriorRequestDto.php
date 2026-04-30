<?php

declare(strict_types=1);

namespace App\Domains\Furniture\DTOs;

final readonly class AIInteriorRequestDto
{
    public function __construct(
        public int $roomTypeId,
        public string $stylePreference,
        public int $budgetKopecks,
        public ?string $photoPath = null,
        public array $existingFurnitureIds = [],
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'room_type_id' => $this->roomTypeId,
            'style_preference' => $this->stylePreference,
            'budget_kopecks' => $this->budgetKopecks,
            'photo_path' => $this->photoPath,
            'existing_furniture_ids' => $this->existingFurnitureIds,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            roomTypeId: $data['room_type_id'],
            stylePreference: $data['style_preference'],
            budgetKopecks: $data['budget_kopecks'],
            photoPath: $data['photo_path'] ?? null,
            existingFurnitureIds: $data['existing_furniture_ids'] ?? [],
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
