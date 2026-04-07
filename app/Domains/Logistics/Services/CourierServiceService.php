<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class CourierServiceService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createCourierService(
            int $tenantId,
            int $userId,
            string $companyName,
            string $licenseNumber,
            array $vehicleTypes,
            int $serviceRadius,
            float $baseRate,
            float $perKmRate,
            string $correlationId
    ): CourierService {

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use (
                $tenantId,
                $userId,
                $companyName,
                $licenseNumber,
                $vehicleTypes,
                $serviceRadius,
                $baseRate,
                $perKmRate,
                $correlationId
    ) {
                $courier = CourierService::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'company_name' => $companyName,
                    'license_number' => $licenseNumber,
                    'vehicle_types' => collect($vehicleTypes),
                    'service_radius' => $serviceRadius,
                    'base_rate' => $baseRate,
                    'per_km_rate' => $perKmRate,
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Courier service created', [
                    'courier_id' => $courier->id,
                    'tenant_id' => $tenantId,
                    'company_name' => $companyName,
                    'correlation_id' => $correlationId,
                ]);

                return $courier;
            });
        }

        public function updateCourierService(CourierService $courier, array $data, string $correlationId): CourierService
        {

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($courier, $data, $correlationId) {
                $courier->update([...$data, 'correlation_id' => $correlationId]);

                $this->logger->info('Courier service updated', [
                    'courier_id' => $courier->id,
                    'correlation_id' => $correlationId,
                ]);

                return $courier;
            });
        }
}
