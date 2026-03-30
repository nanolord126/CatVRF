<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RealEstateService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudMLService $fraudService
        ) {}

        /**
         * Создать новый объект недвижимости
         *
         * @param array $data
         * @param string $correlationId
         * @return Property
         */
        public function createProperty(array $data, string $correlationId): Property
        {
            return DB::transaction(function () use ($data, $correlationId) {
                Log::channel('audit')->info('Creating property start', [
                    'data' => $data,
                    'correlation_id' => $correlationId
                ]);

                $property = Property::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                    'status' => 'available'
                ]));

                Log::channel('audit')->info('Property created successfully', [
                    'property_id' => $property->id,
                    'correlation_id' => $correlationId
                ]);

                return $property;
            });
        }

        /**
         * Создать объявление (Listing)
         *
         * @param array $data
         * @param string $correlationId
         * @return Listing
         */
        public function createListing(array $data, string $correlationId): Listing
        {
            return DB::transaction(function () use ($data, $correlationId) {
                // Fraud check перед публикацией
                $this->fraudService->checkListingAbuse($data, $correlationId);

                $listing = Listing::create(array_merge($data, [
                    'uuid' => (string) Str::uuid(),
                    'correlation_id' => $correlationId,
                    'status' => 'active',
                    'published_at' => now(),
                ]));

                Log::channel('audit')->info('RealEstate listing created', [
                    'listing_id' => $listing->id,
                    'deal_type' => $listing->deal_type,
                    'correlation_id' => $correlationId
                ]);

                return $listing;
            });
        }

        /**
         * Оформление договора аренды
         *
         * @param Listing $listing
         * @param array $tenantData
         * @param string $correlationId
         * @return RentalContract
         */
        public function signRentalContract(Listing $listing, array $tenantData, string $correlationId): RentalContract
        {
            return DB::transaction(function () use ($listing, $tenantData, $correlationId) {
                // Блокируем объявление на время сделки
                $listing->lockForUpdate();

                if ($listing->status !== 'active') {
                    throw new \Exception("Listing is not active and cannot be rented.");
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
                    'terms' => array_merge($listing->rules, ['signed_at' => now()])
                ]);

                // Обновляем статус объявления и объекта
                $listing->update(['status' => 'rented']);
                $listing->property->update(['status' => 'occupied']);

                Log::channel('audit')->info('Rental contract signed', [
                    'contract_id' => $contract->id,
                    'listing_id' => $listing->id,
                    'correlation_id' => $correlationId
                ]);

                return $contract;
            });
        }

        /**
         * Завершение аренды (Check-out)
         *
         * @param RentalContract $contract
         * @param string $correlationId
         */
        public function completeRental(RentalContract $contract, string $correlationId): void
        {
            DB::transaction(function () use ($contract, $correlationId) {
                $contract->update(['contract_status' => 'completed', 'end_date' => now()]);
                $contract->listing->update(['status' => 'active']);
                $contract->listing->property->update(['status' => 'available']);

                Log::channel('audit')->info('Rental contract completed (check-out)', [
                    'contract_uuid' => $contract->uuid,
                    'correlation_id' => $correlationId
                ]);
            });
        }
}
