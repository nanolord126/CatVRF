<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class HomeServicesService
{
    public function __construct(private FraudControlService $fraud,
        private \App\Services\AuditService $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function bookService(array $data, int $userId, int $tenantId): HomeServiceJob
        {
            $correlationId = $data['correlation_id'] ?? \Illuminate\Support\Str::uuid()->toString();

            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'home_service_booking',
                amount: (int) ($data['price'] ?? 0),
                correlationId: $correlationId
            );

            return $this->db->transaction(function () use ($data, $userId, $tenantId, $correlationId) {
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

            $this->logger->info('Home service job booked', [
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
