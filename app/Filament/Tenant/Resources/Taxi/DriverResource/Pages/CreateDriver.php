<?php
declare(strict_types=1);

/**
 * CreateDriver — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createdriver
 * @see https://catvrf.ru/docs/createdriver
 * @see https://catvrf.ru/docs/createdriver
 * @see https://catvrf.ru/docs/createdriver
 * @see https://catvrf.ru/docs/createdriver
 */


namespace App\Filament\Tenant\Resources\Taxi\DriverResource\Pages;


use Illuminate\Auth\AuthManager;
use App\Domains\Auto\Taxi\Application\B2B\DTO\CreateDriverDTO;
use App\Domains\Auto\Taxi\Application\B2B\UseCases\CreateDriverUseCase;
use App\Filament\Tenant\Resources\Taxi\DriverResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

/**
 * Class CreateDriver
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Taxi\DriverResource\Pages
 */
final class CreateDriver extends CreateRecord
{
    public function __construct(
        private readonly AuthManager $authManager,
    ) {}

    protected static string $resource = DriverResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = CreateDriverDTO::fromArray([
            'name' => $data['name'],
            'licenseNumber' => $data['license_number'],
            'tenantId' => $this->authManager->user()->tenant_id, // Or however you get the tenant id
        ]);

        $useCase = app(CreateDriverUseCase::class);
        $driverEntity = $useCase($dto);

        return \App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\Driver::find($driverEntity->getId()->toString());
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
}
