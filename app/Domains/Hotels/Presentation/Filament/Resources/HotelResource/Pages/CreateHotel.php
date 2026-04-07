<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Filament\Resources\HotelResource\Pages;


use Psr\Log\LoggerInterface;
use App\Domains\Hotels\Application\UseCases\B2B\CreateHotelUseCase;
use App\Domains\Hotels\Presentation\Filament\Resources\HotelResource;
use Filament\Resources\Pages\CreateRecord;
use App\Domains\Hotels\Application\DTO\HotelDTO;
use App\Domains\Hotels\Application\DTO\AddressDTO;
use Illuminate\Support\Str;

final class CreateHotel extends CreateRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    protected static string $resource = HotelResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $correlationId = Str::uuid()->toString();

        try {
            return $this->db->transaction(function () use ($data, $correlationId) {
                /** @var CreateHotelUseCase $useCase */
                $useCase = app(CreateHotelUseCase::class);

                $addressData = $data['address'];
                $addressDto = new AddressDTO(
                    country: $addressData['country'],
                    city: $addressData['city'],
                    street: $addressData['street'],
                    houseNumber: $addressData['house_number'],
                    zipCode: $addressData['zip_code'] ?? null
                );

                $hotelDto = new HotelDTO(
                    tenantId: filament()->getTenant()->id,
                    name: $data['name'],
                    description: $data['description'],
                    address: $addressDto,
                    amenities: $data['amenities'] ?? [],
                    correlationId: $correlationId
                );

                $hotelId = $useCase->execute($hotelDto);

                $this->logger->info('Hotel created successfully.', [
                    'hotel_id' => $hotelId->toString(),
                    'tenant_id' => filament()->getTenant()->id,
                    'correlation_id' => $correlationId,
                ]);

                return static::getModel()::find($hotelId->toString());
            });
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create hotel.', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
