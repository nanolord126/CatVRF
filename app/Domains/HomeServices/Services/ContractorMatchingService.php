<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;

final readonly class ContractorMatchingService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function __construct(private readonly DatabaseManager $db, private readonly LoggerInterface $logger) {}

    public function findContractors(string $serviceType, string $address, string $correlationId): Collection
    {

        try {
            $contractors = $this->db->table('contractors')
                ->where('service_type', $serviceType)
                ->where('is_available', true)
                ->orderBy('rating', 'desc')
                ->limit(10)
                ->get();

            $this->logger->$this->logger->info('Contractors found', [
                'service_type' => $serviceType,
                'count' => $contractors->count(),
                'correlation_id' => $correlationId,
            ]);

            return $contractors;
        } catch (\Throwable $e) {
            $this->logger->error('Contractor matching failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
