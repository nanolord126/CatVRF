<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Entities;

use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Shared\Domain\Entities\Entity;
use App\Shared\Domain\ValueObjects\Photo;

/**
 * Class PortfolioItem
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Beauty\Domain\Entities
 */
final class PortfolioItem extends Entity
{
    public function __construct(
        private int $id,
        private MasterId $masterId,
        private Photo $photo,
        private string $description,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMasterId(): MasterId
    {
        return $this->masterId;
    }

    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Обновить фото в портфолио.
     */
    public function updatePhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    /**
     * Обновить описание работы.
     *
     * @throws \InvalidArgumentException
     */
    public function updateDescription(string $description): void
    {
        if (empty($description)) {
            throw new \InvalidArgumentException('Portfolio item description cannot be empty.');
        }

        $this->description = $description;
    }

    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'master_id' => $this->masterId->getValue(),
            'photo_url' => $this->photo->getUrl(),
            'description' => $this->description,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->description) && $this->photo->getUrl() !== '';
    }
}
