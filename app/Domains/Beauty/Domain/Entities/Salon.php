<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Entities;

use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Domain\ValueObjects\Schedule;
use App\Shared\Domain\Entities\Entity;
use App\Shared\Domain\ValueObjects\Address;
use App\Shared\Domain\ValueObjects\Photo;
use App\Shared\Domain\ValueObjects\TenantId;
use Illuminate\Support\Collection;

final class Salon extends Entity
{
    public function __construct(
        private SalonId $id,
        private TenantId $tenantId,
        private string $name,
        private Address $address,
        private Schedule $schedule,
        private ?Photo $previewPhoto = null,
        private Collection $masters,
        private Collection $services,
        private float $rating = 0.0,
        private int $reviewCount = 0,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public function getId(): SalonId
    {
        return $this->id;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule;
    }

    public function getPreviewPhoto(): ?Photo
    {
        return $this->previewPhoto;
    }

    public function getMasters(): Collection
    {
        return $this->masters;
    }

    public function getServices(): Collection
    {
        return $this->services;
    }

    public function getRating(): float
    {
        return $this->rating;
    }

    public function getReviewCount(): int
    {
        return $this->reviewCount;
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

    public function changeAddress(Address $address): void
    {
        $this->address = $address;
        $this->touch();
    }

    public function changeSchedule(Schedule $schedule): void
    {
        $this->schedule = $schedule;
        $this->touch();
    }

    public function setPreviewPhoto(?Photo $photo): void
    {
        $this->previewPhoto = $photo;
        $this->touch();
    }

    /**
     * @param Master $master
     * @return void
     */
    public function addMaster(Master $master): void
    {
        if ($this->masters->contains(fn(Master $m) => $m->getId()->equals($master->getId()))) {
            // Or throw an exception
            return;
        }
        $this->masters->add($master);
        $this->touch();
    }

    /**
     * @param Service $service
     * @return void
     */
    public function addService(Service $service): void
    {
        if ($this->services->contains(fn(Service $s) => $s->getId()->equals($service->getId()))) {
            return;
        }
        $this->services->add($service);
        $this->touch();
    }

    /**
     * @param float $newRating
     * @return void
     */
    public function updateRating(float $newRating): void
    {
        $this->rating = (($this->rating * $this->reviewCount) + $newRating) / ($this->reviewCount + 1);
        $this->reviewCount++;
        $this->touch();
    }

    /**
     * @return void
     */
    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'tenant_id' => $this->tenantId->getValue(),
            'name' => $this->name,
            'address' => $this->address->toArray(),
            'schedule' => $this->schedule->getWeeklySchedule(),
            'preview_photo' => $this->previewPhoto?->getUrl(),
            'rating' => $this->rating,
            'review_count' => $this->reviewCount,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
