<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Entities;

use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Domain\ValueObjects\Schedule;
use App\Shared\Domain\Entities\Entity;
use App\Shared\Domain\ValueObjects\Photo;
use Illuminate\Support\Collection;

final class Master extends Entity
{
    public function __construct(
        private MasterId $id,
        private SalonId $salonId,
        private string $name,
        private string $specialization,
        private int $experienceYears,
        private Schedule $schedule,
        private ?Photo $photo = null,
        private Collection $services,
        private Collection $portfolio,
        private float $rating = 0.0,
        private int $reviewCount = 0,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public function getId(): MasterId
    {
        return $this->id;
    }

    public function getSalonId(): SalonId
    {
        return $this->salonId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSpecialization(): string
    {
        return $this->specialization;
    }

    public function getExperienceYears(): int
    {
        return $this->experienceYears;
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule;
    }

    public function getPhoto(): ?Photo
    {
        return $this->photo;
    }

    public function getServices(): Collection
    {
        return $this->services;
    }

    public function getPortfolio(): Collection
    {
        return $this->portfolio;
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

    public function changeSpecialization(string $specialization): void
    {
        $this->specialization = $specialization;
        $this->touch();
    }

    public function setPhoto(?Photo $photo): void
    {
        $this->photo = $photo;
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
     * @param PortfolioItem $item
     * @return void
     */
    public function addToPortfolio(PortfolioItem $item): void
    {
        $this->portfolio->add($item);
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
            'salon_id' => $this->salonId->getValue(),
            'name' => $this->name,
            'specialization' => $this->specialization,
            'experience_years' => $this->experienceYears,
            'schedule' => $this->schedule->getWeeklySchedule(),
            'photo' => $this->photo?->getUrl(),
            'rating' => $this->rating,
            'review_count' => $this->reviewCount,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
