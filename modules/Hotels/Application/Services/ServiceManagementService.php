<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\Services;

use Modules\Hotels\Domain\Entities\Service;
use Modules\Hotels\Domain\Enums\ServiceType;
use Modules\Hotels\Domain\Repositories\ServiceRepositoryInterface;
use Modules\Hotels\Domain\ValueObjects\VenueId;
use Modules\Hotels\Domain\ValueObjects\TenantId;
use Illuminate\Support\Facades\Log;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class ServiceManagementService
{
    use WithAuditLogging;

    public function __construct(
        private ServiceRepositoryInterface $serviceRepository,
        private readonly AuditService $auditService,
    ) {}

    public function createService(
        TenantId $tenantId,
        VenueId $venueId,
        string $name,
        ServiceType $type,
        float $basePrice,
        string $currency = 'RUB',
        ?string $description = null,
        bool $isAvailable = true,
        bool $isOptional = true,
        ?string $icon = null,
        ?int $durationMinutes = null,
        ?int $maxQuantityPerBooking = null,
        int $sortOrder = 0,
    ): Service {
        $service = Service::create(
            tenantId: $tenantId,
            venueId: $venueId,
            name: $name,
            type: $type,
            basePrice: $basePrice,
            currency: $currency,
            description: $description,
            isAvailable: $isAvailable,
            isOptional: $isOptional,
            icon: $icon,
            durationMinutes: $durationMinutes,
            maxQuantityPerBooking: $maxQuantityPerBooking,
            sortOrder: $sortOrder,
        );

        $saved = $this->serviceRepository->save($service);

        Log::info('Service created', [
            'service_id' => $service->id,
            'venue_id' => $venueId->value,
            'name' => $name,
            'type' => $type->value,
        ]);

        $this->logCreated('Service', $saved->id, [
            'name' => $name,
            'type' => $type->value,
        ], null, $tenantId->value);

        return $saved;
    }

    public function updateServicePrice(int $serviceId, float $newPrice): Service
    {
        $service = $this->serviceRepository->findById(\Modules\Hotels\Domain\ValueObjects\ServiceId::fromInt($serviceId));
        if ($service === null) {
            throw new \InvalidArgumentException('Service not found');
        }

        $updatedService = $service->updatePrice($newPrice);
        $this->serviceRepository->save($updatedService);

        Log::info('Service price updated', [
            'service_id' => $serviceId,
            'new_price' => $newPrice,
        ]);

        $this->logUpdated('Service', $serviceId, [
            'new_price' => $newPrice,
        ], null, $service->tenantId->value);

        return $updatedService;
    }

    public function activateService(int $serviceId): Service
    {
        $service = $this->serviceRepository->findById(\Modules\Hotels\Domain\ValueObjects\ServiceId::fromInt($serviceId));
        if ($service === null) {
            throw new \InvalidArgumentException('Service not found');
        }

        $updatedService = $service->activate();
        $this->serviceRepository->save($updatedService);

        $this->logAction('service_activated', 'Service', $serviceId, [], null, $service->tenantId->value);

        return $updatedService;
    }

    public function deactivateService(int $serviceId): Service
    {
        $service = $this->serviceRepository->findById(\Modules\Hotels\Domain\ValueObjects\ServiceId::fromInt($serviceId));
        if ($service === null) {
            throw new \InvalidArgumentException('Service not found');
        }

        $updatedService = $service->deactivate();
        $this->serviceRepository->save($updatedService);

        $this->logAction('service_deactivated', 'Service', $serviceId, [], null, $service->tenantId->value);

        return $updatedService;
    }

    public function getAvailableServices(VenueId $venueId): array
    {
        return $this->serviceRepository->findAvailableByVenue($venueId);
    }

    public function calculateServiceCost(int $serviceId, int $quantity = 1): float
    {
        $service = $this->serviceRepository->findById(\Modules\Hotels\Domain\ValueObjects\ServiceId::fromInt($serviceId));
        if ($service === null) {
            throw new \InvalidArgumentException('Service not found');
        }

        return $service->calculatePrice($quantity);
    }
}
