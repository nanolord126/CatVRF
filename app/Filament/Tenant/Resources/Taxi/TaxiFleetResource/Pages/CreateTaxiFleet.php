<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Taxi\TaxiFleetResource\Pages;


use Illuminate\Auth\AuthManager;
use App\Domains\Auto\Taxi\Application\B2B\DTO\CreateTaxiFleetDTO;
use App\Domains\Auto\Taxi\Application\B2B\UseCases\CreateTaxiFleetUseCase;
use App\Filament\Tenant\Resources\Taxi\TaxiFleetResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

/**
 * Class CreateTaxiFleet
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Taxi\TaxiFleetResource\Pages
 */
final class CreateTaxiFleet extends CreateRecord
{
    public function __construct(
        private readonly AuthManager $authManager,
    ) {}

    protected static string $resource = TaxiFleetResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = CreateTaxiFleetDTO::fromArray([
            'name' => $data['name'],
            'tenantId' => $this->authManager->user()->tenant_id, // Or however you get the tenant id
        ]);

        $useCase = app(CreateTaxiFleetUseCase::class);
        $fleetEntity = $useCase($dto);

        return \App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\TaxiFleet::find($fleetEntity->getId()->toString());
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
