<?php declare(strict_types=1);

/**
 * FitnessGymService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fitnessgymservice
 */


namespace App\Domains\Sports\Fitness\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class FitnessGymService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createGym(array $data, int $tenantId, string $correlationId): FitnessGym
        {

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($data, $tenantId, $correlationId) {
                $this->logger->info('Creating fitness gym', ['correlation_id' => $correlationId]);

                return FitnessGym::create([
                    'tenant_id' => $tenantId,
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'geo_point' => $data['geo_point'] ?? null,
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
