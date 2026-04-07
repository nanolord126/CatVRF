<?php declare(strict_types=1);

namespace App\Domains\Sports\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
/**
 * Class SportVenueService
 *
 * Part of the Sports vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Sports\Services
 */
final readonly class SportVenueService
{
    public function __construct(private FraudControlService $fraud,
        private \App\Services\AuditService $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createVenue(array $data, int $tenantId, string $correlationId): SportVenue
        {
            $correlationId = $correlationId ?: Str::uuid()->toString();
            $this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'sports_venue_create',
                amount: 0,
                correlationId: $correlationId
            );

            return $this->db->transaction(function () use ($data, $tenantId, $correlationId) {
                $this->logger->info('Creating sport venue', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                ]);

                return SportVenue::create([
                    'tenant_id' => $tenantId,
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'geo_point' => $data['geo_point'] ?? null,
                    'sports_types' => json_encode($data['sports_types'] ?? []),
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
