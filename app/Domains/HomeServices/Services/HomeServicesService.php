<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Services\AuditService;
use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use Illuminate\Database\DatabaseManager;

final readonly class HomeServicesService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterf
    ) {}

    public function bookService(array $data, int $userId, int $tenantId): HomeServiceJob
    {
        $correlationId = $data['correlation_id'] ?? \Illuminate\Support\Str::uuid()->toString();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'home_service_booking',
            amount: (int) ($data['price'] ?? 0),
            correlationId: $correlationId
        );

        // Check if address is in service delivery zone
        if (isset($data['address'])) {
            $isInZone = $this->geoAdapter->isAddressInDeliveryZone(
                address: $data['address'],
                vertical: 'home_services'
            );
            if (!$isInZone) {
                throw new \RuntimeException('Address is outside service delivery zone');
            }
        }

        return $this->db->transaction(function () use ($data, $userId, $tenantId) {
            $job = HomeServiceJob::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'contractor_id' => $data['contractor_id'],
                'client_id' => $userId,
                'service_type' => $data['service_type'],
                'datetime' => $data['datetime'],
                'address' => $data['address'],
                'price' => $data['price'],
                'status' => 'pending',
            ]);

            $this->logger->$this->logger->info('Home service job booked', [
                'correlation_id' => $this->correlationId,
                'job_id' => $job->id,
            ]);

            return $job;
        });
    }

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable $callback)
    {

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
        $this->db->transaction(function () use ($callback) {
            return $callback();
        });
    }
}
