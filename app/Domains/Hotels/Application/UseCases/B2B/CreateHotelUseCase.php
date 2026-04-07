<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Application\UseCases\B2B;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Hotels\Application\DTO\HotelDTO;
use App\Domains\Hotels\Domain\Repositories\HotelRepositoryInterface;
use App\Domains\Hotels\Domain\Entities\Hotel;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\Address;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

final class CreateHotelUseCase
{
    public function __construct(private readonly HotelRepositoryInterface $hotelRepository,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {
    }

    public function __invoke(HotelDTO $hotelDTO): HotelId
    {
        $correlationId = $hotelDTO->correlation_id ?? Str::uuid()->toString();

        $this->db->beginTransaction();

        try {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'create_hotel', amount: 0, correlationId: $correlationId ?? '');

            $hotel = new Hotel(
                id: HotelId::random(),
                tenantId: $hotelDTO->tenant_id,
                name: $hotelDTO->name,
                address: new Address(
                    country: $hotelDTO->address->country,
                    city: $hotelDTO->address->city,
                    street: $hotelDTO->address->street,
                    houseNumber: $hotelDTO->address->house_number,
                    zipCode: $hotelDTO->address->zip_code
                ),
                description: $hotelDTO->description,
                rooms: new Collection(),
                amenities: new Collection($hotelDTO->amenities),
                correlationId: $correlationId
            );

            $this->hotelRepository->save($hotel);

            $this->db->commit();

            $this->logger->info('Hotel created successfully', [
                'hotel_id' => $hotel->getId()->toString(),
                'tenant_id' => $hotel->getTenantId(),
                'correlation_id' => $correlationId,
            ]);

            return $hotel->getId();
        } catch (\Throwable $e) {
            $this->db->rollBack();

            $this->logger->error('Failed to create hotel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
