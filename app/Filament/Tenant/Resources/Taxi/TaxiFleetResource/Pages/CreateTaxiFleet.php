<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Taxi\TaxiFleetResource\Pages;

use CreateTaxiFleetUseCase;

use Illuminate\Auth\AuthManager;
use App\Domains\Auto\Taxi\Application\B2B\DTO\CreateTaxiFleetDTO;
use App\Domains\Auto\Taxi\Application\B2B\UseCases\CreateTaxiFleetUseCase;
use App\Filament\Tenant\Resources\Taxi\TaxiFleetResource;
use Filament\Resources\Pages\CreateRecord;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\TaxiFleet;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CreateTaxiFleet
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateTaxiFleet extends CreateRecord
{
    protected static string $resource = TaxiFleetResource::class;

    public function __construct(private readonly CreateTaxiFleetUseCase $createTaxiFleetUseCase,
        private readonly AuthManager $authManager,) {}

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $dto = CreateTaxiFleetDTO::fromArray([
            'name' => $data['name'],
            'tenantId' => $this->authManager->user()->tenant_id, // Or however you get the tenant id
        ]);

        $useCase = $this->createTaxiFleetUseCase /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
        $fleetEntity = $useCase($dto);

        return TaxiFleet::find($fleetEntity->getId()->toString());
    }
}
