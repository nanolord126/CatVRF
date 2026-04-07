<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\Entities;

use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use DomainException;

final class RealEstateAgent
{
    /** @var list<string> */
    private array $assignedPropertyIds = [];

    private bool $isActive;

    private float $rating;

    private int $dealsCount;

    public function __construct(
        private readonly AgentId $id,
        private readonly int     $tenantId,
        private readonly int     $userId,
        private string           $fullName,
        private string           $phone,
        private string           $email,
        private string           $licenseNumber,
        private string           $correlationId,
        float                    $rating = 0.0,
        int                      $dealsCount = 0,
        bool                     $isActive = true) {
        $this->rating     = $rating;
        $this->dealsCount = $dealsCount;
        $this->isActive   = $isActive;
    }

    public function assignProperty(PropertyId $propertyId): void
    {
        if (in_array($propertyId->getValue(), $this->assignedPropertyIds, strict: true)) {
            return;
        }

        $this->assignedPropertyIds[] = $propertyId->getValue();
    }

    public function removeProperty(PropertyId $propertyId): void
    {
        $this->assignedPropertyIds = array_values(
            array_filter(
                $this->assignedPropertyIds,
                static fn (string $id): bool => $id !== $propertyId->getValue(),
            )
        );
    }

    public function updateContactInfo(string $phone, string $email): void
    {
        $this->phone = $phone;
        $this->email = $email;
    }

    public function updateLicenseNumber(string $licenseNumber): void
    {
        $this->licenseNumber = $licenseNumber;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function incrementDealsCount(): void
    {
        $this->dealsCount++;
    }

    public function updateRating(float $newRating): void
    {
        if ($newRating < 0.0 || $newRating > 5.0) {
            throw new DomainException("Rating must be between 0.0 and 5.0, got {$newRating}.");
        }

        $this->rating = $newRating;
    }

    public function getId(): AgentId { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getUserId(): int { return $this->userId; }
    public function getFullName(): string { return $this->fullName; }
    public function getPhone(): string { return $this->phone; }
    public function getEmail(): string { return $this->email; }
    public function getLicenseNumber(): string { return $this->licenseNumber; }
    public function getCorrelationId(): string { return $this->correlationId; }
    public function isActive(): bool { return $this->isActive; }
    public function getRating(): float { return $this->rating; }
    public function getDealsCount(): int { return $this->dealsCount; }

    /** @return list<string> */
    public function getAssignedPropertyIds(): array { return $this->assignedPropertyIds; }
}
