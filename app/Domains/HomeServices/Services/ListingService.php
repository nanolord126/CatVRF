<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class ListingService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createListing(
            int $contractorId,
            int $categoryId,
            string $name,
            string $description,
            string $type,
            float $basePrice,
            string $correlationId
        ): ServiceListing {

            try {
                            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($contractorId, $categoryId, $name, $description, $type, $basePrice, $correlationId) {
                    $listing = ServiceListing::create([
                        'tenant_id' => tenant()->id,
                        'contractor_id' => $contractorId,
                        'category_id' => $categoryId,
                        'name' => $name,
                        'description' => $description,
                        'type' => $type,
                        'base_price' => $basePrice,
                        'is_active' => true,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Service listing created', [
                        'listing_id' => $listing->id,
                        'contractor_id' => $contractorId,
                        'correlation_id' => $correlationId,
                    ]);

                    return $listing;
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create listing', ['error' => $e->getMessage()]);
                throw $e;
            }
        }

        public function updateListing(ServiceListing $listing, array $data, string $correlationId): ServiceListing
        {

            try {
                            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($listing, $data, $correlationId) {
                    $listing->update($data + ['correlation_id' => $correlationId]);
                    return $listing;
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update listing', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
}
