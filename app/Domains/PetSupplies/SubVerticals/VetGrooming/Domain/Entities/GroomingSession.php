<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\VetGrooming\Domain\Enums\GroomingServiceType;
use Modules\VetGrooming\Domain\Enums\GroomingStatus;
use Modules\VetGrooming\Domain\Enums\BehaviorRating;

final readonly class GroomingSession
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public ?int $clinicId,
        public int $petId,
        public ?int $groomerId,
        public ?int $appointmentId,
        public ?int $serviceId,
        public ?CarbonImmutable $startedAt,
        public ?CarbonImmutable $completedAt,
        public ?int $durationMinutes,
        public GroomingServiceType $serviceType,
        public ?BehaviorRating $behaviorRating,
        public ?string $behaviorNotes,
        public ?array $productsUsed,
        public ?array $beforePhotos,
        public ?array $afterPhotos,
        public ?string $allergyAlerts,
        public ?string $medicalNotes,
        public GroomingStatus $status,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $petId,
        GroomingServiceType $serviceType,
        ?int $clinicId = null,
        ?int $groomerId = null,
        ?int $appointmentId = null,
        ?int $serviceId = null,
        ?CarbonImmutable $startedAt = null,
        ?string $correlationId = null,
        ?array $tags = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            clinicId: $clinicId,
            petId: $petId,
            groomerId: $groomerId,
            appointmentId: $appointmentId,
            serviceId: $serviceId,
            startedAt: $startedAt,
            completedAt: null,
            durationMinutes: null,
            serviceType: $serviceType,
            behaviorRating: null,
            behaviorNotes: null,
            productsUsed: null,
            beforePhotos: null,
            afterPhotos: null,
            allergyAlerts: null,
            medicalNotes: null,
            status: GroomingStatus::SCHEDULED,
            correlationId: $correlationId,
            tags: $tags,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function start(?int $groomerId = null): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            clinicId: $this->clinicId,
            petId: $this->petId,
            groomerId: $groomerId ?? $this->groomerId,
            appointmentId: $this->appointmentId,
            serviceId: $this->serviceId,
            startedAt: CarbonImmutable::now(),
            completedAt: null,
            durationMinutes: null,
            serviceType: $this->serviceType,
            behaviorRating: $this->behaviorRating,
            behaviorNotes: $this->behaviorNotes,
            productsUsed: $this->productsUsed,
            beforePhotos: $this->beforePhotos,
            afterPhotos: $this->afterPhotos,
            allergyAlerts: $this->allergyAlerts,
            medicalNotes: $this->medicalNotes,
            status: GroomingStatus::IN_PROGRESS,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function complete(
        ?BehaviorRating $behaviorRating = null,
        ?string $behaviorNotes = null,
        ?array $productsUsed = null,
        ?array $beforePhotos = null,
        ?array $afterPhotos = null,
        ?string $medicalNotes = null,
    ): self {
        $completedAt = CarbonImmutable::now();
        $durationMinutes = $this->startedAt 
            ? $this->startedAt->diffInMinutes($completedAt) 
            : null;

        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            clinicId: $this->clinicId,
            petId: $this->petId,
            groomerId: $this->groomerId,
            appointmentId: $this->appointmentId,
            serviceId: $this->serviceId,
            startedAt: $this->startedAt,
            completedAt: $completedAt,
            durationMinutes: $durationMinutes,
            serviceType: $this->serviceType,
            behaviorRating: $behaviorRating,
            behaviorNotes: $behaviorNotes,
            productsUsed: $productsUsed,
            beforePhotos: $beforePhotos ?? $this->beforePhotos,
            afterPhotos: $afterPhotos,
            allergyAlerts: $this->allergyAlerts,
            medicalNotes: $medicalNotes,
            status: GroomingStatus::COMPLETED,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addBeforePhotos(array $photos): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            clinicId: $this->clinicId,
            petId: $this->petId,
            groomerId: $this->groomerId,
            appointmentId: $this->appointmentId,
            serviceId: $this->serviceId,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            durationMinutes: $this->durationMinutes,
            serviceType: $this->serviceType,
            behaviorRating: $this->behaviorRating,
            behaviorNotes: $this->behaviorNotes,
            productsUsed: $this->productsUsed,
            beforePhotos: $photos,
            afterPhotos: $this->afterPhotos,
            allergyAlerts: $this->allergyAlerts,
            medicalNotes: $this->medicalNotes,
            status: $this->status,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addAfterPhotos(array $photos): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            clinicId: $this->clinicId,
            petId: $this->petId,
            groomerId: $this->groomerId,
            appointmentId: $this->appointmentId,
            serviceId: $this->serviceId,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            durationMinutes: $this->durationMinutes,
            serviceType: $this->serviceType,
            behaviorRating: $this->behaviorRating,
            behaviorNotes: $this->behaviorNotes,
            productsUsed: $this->productsUsed,
            beforePhotos: $this->beforePhotos,
            afterPhotos: $photos,
            allergyAlerts: $this->allergyAlerts,
            medicalNotes: $this->medicalNotes,
            status: $this->status,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function setAllergyAlerts(string $alerts): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            clinicId: $this->clinicId,
            petId: $this->petId,
            groomerId: $this->groomerId,
            appointmentId: $this->appointmentId,
            serviceId: $this->serviceId,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            durationMinutes: $this->durationMinutes,
            serviceType: $this->serviceType,
            behaviorRating: $this->behaviorRating,
            behaviorNotes: $this->behaviorNotes,
            productsUsed: $this->productsUsed,
            beforePhotos: $this->beforePhotos,
            afterPhotos: $this->afterPhotos,
            allergyAlerts: $alerts,
            medicalNotes: $this->medicalNotes,
            status: $this->status,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function hasBeforePhotos(): bool
    {
        return ! empty($this->beforePhotos);
    }

    public function hasAfterPhotos(): bool
    {
        return ! empty($this->afterPhotos);
    }

    public function hasAllergyAlerts(): bool
    {
        return ! empty($this->allergyAlerts);
    }

    public function isAggressiveBehavior(): bool
    {
        return $this->behaviorRating === BehaviorRating::AGGRESSIVE;
    }
}
