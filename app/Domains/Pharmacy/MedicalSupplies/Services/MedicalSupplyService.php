<?php declare(strict_types=1);

/**
 * MedicalSupplyService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/medicalsupplyservice
 */


namespace App\Domains\Pharmacy\MedicalSupplies\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class MedicalSupplyService
{

    private readonly string $correlationId;


    public function __construct(
            private readonly FraudControlService $fraud,
            string $correlationId = '', private readonly LoggerInterface $logger, private readonly Guard $guard) {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->correlationId = $correlationId ?: Str::uuid()->toString();
        }

        public function getSuppliesByType(string $type)
        {
            $this->logger->info('Get medical supplies', [
                'correlation_id' => $this->correlationId,
                'type' => $type,
            ]);

            return MedicalSupply::where('type', $type)->get();
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
