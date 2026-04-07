<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Entities;

use App\Domains\Beauty\Domain\Enums\ServiceCategory;
use App\Domains\Beauty\Domain\ValueObjects\Duration;
use App\Domains\Beauty\Domain\ValueObjects\Price;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Shared\Domain\Entities\Entity;

final class Service extends Entity
{
    public function __construct(
        private ServiceId $id,
        private string $name,
        private ServiceCategory $category,
        private Price $price,
        private Duration $duration,
        private string $description,
        private bool $isActive = true,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public function getId(): ServiceId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): ServiceCategory
    {
        return $this->category;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getDuration(): Duration
    {
        return $this->duration;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changeName(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function changeDescription(string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->touch();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->touch();
    }

    public function changePrice(Price $newPrice): void
    {
        if ($this->price->equals($newPrice)) {
            return;
        }
        $this->price = $newPrice;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'name' => $this->name,
            'category' => $this->category->value,
            'price' => $this->price->getAmountInCents(),
            'duration' => $this->duration->getMinutes(),
            'description' => $this->description,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
