<?php declare(strict_types=1);

namespace App\Services\Dental;



use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class DentalService
{

    public function __construct(
            private \App\Services\FraudControlService $fraud,
            private PricingService $pricingService,
            private string $correlation_id = '',
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {
            $this->correlation_id = empty($correlation_id) ? (string) Str::uuid() : $correlation_id;
        }

        /**
         * Get all available services for a clinic with tenant scoping.
         */
        public function getClinicServices(int $clinicId): Collection
        {
            return DentalModel::where('clinic_id', $clinicId)
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get();
        }

        /**
         * Create a new dental service with thorough validation and auditing.
         */
        public function createService(array $data): DentalModel
        {
            return $this->db->transaction(function () use ($data) {
                // 1. Fraud Check
                $this->fraud->check(['operation' => 'create_dental_service', 'data' => $data]);

                // 2. Audit Log
                $this->logger->channel('audit')->info('Creating dental service', [
                    'name' => $data['name'],
                    'clinic_id' => $data['clinic_id'],
                    'correlation_id' => $this->correlation_id,
                ]);

                // 3. Create Service
                $service = DentalModel::create(array_merge($data, [
                    'correlation_id' => $this->correlation_id,
                    'uuid' => (string) Str::uuid(),
                ]));

                if (!$service) {
                    throw new \RuntimeException('Failed to create dental service (DB error)');
                }

                return $service;
            });
        }

        /**
         * Update an existing dental service with audit trail.
         */
        public function updateService(int $id, array $data): DentalModel
        {
            return $this->db->transaction(function () use ($id, $data) {
                $service = DentalModel::findOrFail($id);

                // Audit
                $this->logger->channel('audit')->info('Updating dental service', [
                    'service_id' => $id,
                    'old_price' => $service->base_price,
                    'new_price' => $data['base_price'] ?? $service->base_price,
                    'correlation_id' => $this->correlation_id,
                ]);

                $service->update(array_merge($data, [
                    'correlation_id' => $this->correlation_id,
                ]));

                return $service;
            });
        }

        /**
         * Calculate aggregate costs for a group of services.
         */
        public function calculateBatchPrice(array $serviceIds): int
        {
            $services = DentalModel::whereIn('id', $serviceIds)->get();
            if ($services->isEmpty()) {
                throw new \InvalidArgumentException('No dental services found for the provided IDs');
            }

            return $this->pricingService->calculateTotal($services);
        }

        /**
         * Deactivate a service.
         */
        public function deactivateService(int $id): bool
        {
            return $this->db->transaction(function () use ($id) {
                $service = DentalModel::findOrFail($id);
                $this->logger->channel('audit')->warning('Deactivating dental service', [
                    'service_id' => $id,
                    'correlation_id' => $this->correlation_id,
                ]);

                return $service->delete();
            });
        }
}
