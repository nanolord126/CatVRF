<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Domain\Entities;

use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\TaxiFleetId;
use App\Shared\Domain\Entity;
use Illuminate\Support\Collection;

final class TaxiFleet extends Entity
{
    /** @var Collection<int, DriverId> */
    private Collection $drivers;

    public function __construct(
        private readonly TaxiFleetId $id,
        private readonly int $tenantId,
        private string $name,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt) {
        $this->drivers = new Collection();
    }

    public static function create(
        TaxiFleetId $id,
        int $tenantId,
        string $name
    ): self {
        $now = new \DateTimeImmutable();
        return new self(
            $id,
            $tenantId,
            $name,
            $now,
            $now
        );
    }

    public function addDriver(DriverId $driverId): void
    {
        if ($this->drivers->doesntContain($driverId)) {
            $this->drivers->push($driverId);
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function removeDriver(DriverId $driverId): void
    {
        $this->drivers = $this->drivers->filter(fn (DriverId $id) => !$id->equals($driverId));
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): TaxiFleetId
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, DriverId>
     */
    public function getDrivers(): Collection
    {
        return $this->drivers;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'drivers' => $this->drivers->map(fn(DriverId $id) => $id->toString())->all(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
