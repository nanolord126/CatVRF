<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\Entities;

use App\Domains\RealEstate\Domain\Enums\PropertyStatusEnum;
use App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum;
use App\Domains\RealEstate\Domain\Events\PropertyListed;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\Area;
use App\Domains\RealEstate\Domain\ValueObjects\Coordinate;
use App\Domains\RealEstate\Domain\ValueObjects\Price;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use DomainException;

final class Property
{
    private PropertyStatusEnum $status;

    /** @var array<int, array{url: string, caption: string}> */
    private array $photos = [];

    /** @var array<int, array{url: string, type: string, name: string}> */
    private array $documents = [];

    /** @var list<object> */
    private array $domainEvents = [];

    public function __construct(
        private readonly PropertyId        $id,
        private readonly AgentId           $agentId,
        private readonly int               $tenantId,
        private string                     $title,
        protected string                     $description,
        private string                     $address,
        private Coordinate                 $coordinates,
        private readonly PropertyTypeEnum  $type,
        private Price                      $price,
        private readonly Area              $area,
        private readonly int               $rooms,
        private readonly int               $floor,
        private readonly int               $totalFloors,
        private readonly string            $correlationId,
        PropertyStatusEnum                 $status = PropertyStatusEnum::Draft) {
        $this->status = $status;
    }

    /**
     * Publish the property — transitions Draft → Active, emits PropertyListed.
     */
    public function publish(string $correlationId): void
    {
        if ($this->status !== PropertyStatusEnum::Draft) {
            throw new DomainException(
                "Property {$this->id->getValue()} must be in Draft status to be published."
            );
        }

        $this->status = PropertyStatusEnum::Active;

        $this->domainEvents[] = new PropertyListed(
            propertyId: $this->id->getValue(),
            tenantId: $this->tenantId,
            correlationId: $correlationId,
        );
    }

    public function updateDescription(string $title, string $description): void
    {
        $this->title       = $title;
        $this->description = $description;
    }

    public function updatePrice(Price $newPrice): void
    {
        $this->price = $newPrice;
    }

    public function updateCoordinates(Coordinate $coordinates): void
    {
        $this->coordinates = $coordinates;
    }

    public function archive(): void
    {
        if ($this->status->isTerminal()) {
            throw new DomainException('Cannot archive a property that is already in a terminal state.');
        }

        $this->status = PropertyStatusEnum::Archived;
    }

    public function markAsSold(): void
    {
        $this->status = PropertyStatusEnum::Sold;
    }

    public function markAsRented(): void
    {
        $this->status = PropertyStatusEnum::Rented;
    }

    public function addPhoto(string $url, string $caption = ''): void
    {
        $this->photos[] = ['url' => $url, 'caption' => $caption];
    }

    public function addDocument(string $url, string $type, string $name): void
    {
        $this->documents[] = ['url' => $url, 'type' => $type, 'name' => $name];
    }

    public function calculateCommission(): Price
    {
        return $this->price->percentage($this->type->commissionPercent());
    }

    public function getId(): PropertyId { return $this->id; }
    public function getAgentId(): AgentId { return $this->agentId; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getAddress(): string { return $this->address; }
    public function getCoordinates(): Coordinate { return $this->coordinates; }
    public function getType(): PropertyTypeEnum { return $this->type; }
    public function getPrice(): Price { return $this->price; }
    public function getArea(): Area { return $this->area; }
    public function getRooms(): int { return $this->rooms; }
    public function getFloor(): int { return $this->floor; }
    public function getTotalFloors(): int { return $this->totalFloors; }
    public function getStatus(): PropertyStatusEnum { return $this->status; }
    public function getPhotos(): array { return $this->photos; }
    public function getDocuments(): array { return $this->documents; }
    public function getCorrelationId(): string { return $this->correlationId; }
    public function isActive(): bool { return $this->status === PropertyStatusEnum::Active; }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $events             = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
