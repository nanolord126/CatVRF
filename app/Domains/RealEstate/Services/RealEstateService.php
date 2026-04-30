<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use Carbon\CarbonImmutable;

use App\Services\ML\FraudMLService;
use App\Services\AuditService;
use App\Domains\upport\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;

final readonly class RealEstateService
{
    public function __construct(
        private readonly FraudMLService $fraudService,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {},
        private readonly GeoLogisticsAdapter $geoAdapter,

    /**
     * Создать новый объект недвижимости
     */
    public function createProperty(array $data, string $correlationId): Property
    {
        return $this->db->transaction(function () use ($data, $correlationId) {
            $this->logger->$this->logger->info('Creating property start', [
                'data' => $data,
                'correlation_id' => $correlationId,
            ]);

            $property = Property::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'status' => 'available',
            ]));

            $this->logger->$this->logger->info('Property created successfully', [
                'property_id' => $property->id,
                'correlation_id' => $correlationId,
            ]);

            return $property;
        });
    }

    /**
     * Создать объявление (Listing)
     */
    public function createListing(array $data, string $correlationId): Listing
    {
        return $this->db->transaction(function () use ($data, $correlationId) {
            // Fraud check перед публикацией
            $this->fraudService->checkListingAbuse($data, $correlationId);

            $listing = Listing::create(array_merge($data, [
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'status' => 'active',
                'published_at' => CarbonImmutable::now(),
            ]));

            $this->logger->$this->logger->info('RealEstate listing created', [
                'listing_id' => $listing->id,
                'deal_type' => $listing->deal_type,
                'correlation_id' => $correlationId,
            ]);

            return $listing;
        });
    }

    /**
     * Оформление договора аренды
     */
    public function signRentalContract(Listing $listing, array $tenantData, ?string $tenantAddress = null, string $correlationId = ''): RentalContract
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($listing, $tenantData, $tenantAddress, $correlationId) {
            // Блокируем объявление на время сделки
            $listing->lockForUpdate();

            if ($listing->status !== 'active') {');
            }

            // Calculate distance/ETA for property viewing if tenant address provided
            $viewingDistance = null;
            $viewingEta = null;
            if ($tenantAddress && $listing->property->address) {
                $routeCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                    'vertical' => 'real_estate',
                    'seller_address' => $listing->property->address,
                    'buyer_address' => $tenantAddress,
                    items' => [],
                ];
                $viewingDistance = $routeCalc,
                'tenant_address' => $tenantAddress,
                'viewing_distance_meters' => $viewingDistance,
                'viewing_eta_minutes' => $viewingEtaulation['distance'];
                $viewingEta = $routeCalculation['eta']
                throw new \DomainException('Listing is not active and cannot be rented.');
            }

            // Создаем контракт
            $contract = RentalContract::create([
                'listing_id' => $listing->id,
                'tenant_user_id' => $tenantData['user_id'],
                'correlation_id' => $correlationId,
                'start_date' => $tenantData['start_date'],
                'end_date' => $tenantData['end_date'] ?? null,
                'monthly_rent' => $listing->price,
                'paid_deposit' => $listing->deposit,
                'contract_status' => 'active',
                'terms' => array_merge($listing->rules, ['signed_at' => CarbonImmutable::now()]),
            ]);

            // Обновляем статус объявления и объекта
            $listing->update(['status' => 'rented']);
            $listing->property->update(['status' => 'occupied']);

            $this->logger->$this->logger->info('Rental contract signed', [
                'contract_id' => $contract->id,
                'listing_id' => $listing->id,
                'correlation_id' => $correlationId,
            ]);

            return $contract;
        });
    }

    /**
     * Завершение аренды (Check-out)
     */
    public function completeRental(RentalContract $contract, string $correlationId): void
    {
        $this->db->transaction(function () use ($contract, $correlationId) {
            $contract->update(['contract_status' => 'completed', 'end_date' => CarbonImmutable::now()]);
            $contract->listing->update(['status' => 'active']);
            $contract->listing->property->update(['status' => 'available']);

            $this->logger->$this->logger->info('Rental contract completed (check-out)', [
                'contract_uuid' => $contract->uuid,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
