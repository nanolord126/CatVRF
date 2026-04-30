<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Services;

use App\Domains\Advertising\Domain\Entities\AdInventory;
use App\Domains\Advertising\Domain\Interfaces\AdInventoryRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Ad Inventory Service
 *
 * Manages ad inventory availability, reservation, and forecasting.
 * Uses Redis for atomic inventory operations and caching.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class AdInventoryService
{
    private const INVENTORY_LOCK_TTL = 5; // 5 seconds
    private const INVENTORY_CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly AdInventoryRepositoryInterface $inventoryRepository,
        private readonly FraudControlService $fraudService,
        private readonly Dispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Reserve inventory for an auction or RTB bid
     *
     * @throws \RuntimeException
     */
    public function reserveInventory(
        int $inventoryId,
        int $impressions,
        int $userId = 0,
        ?string $correlationId = null,
    ): bool {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('Reserving inventory', [
            'correlation_id' => $correlationId,
            'inventory_id' => $inventoryId,
            'impressions' => $impressions,
        ]);

        // Fraud check
        $this->fraudService->check(
            userId: $userId,
            operationType: 'inventory_reserve',
            amount: 0,
            correlationId: $correlationId,
            context: ['inventory_id' => $inventoryId, 'impressions' => $impressions],
        );

        // Get Redis lock for atomic operation
        $lockKey = "inventory:{$inventoryId}:lock";
        $lock = Redis::lock($lockKey, self::INVENTORY_LOCK_TTL);

        try {
            $lock->block(5);

            $inventory = $this->inventoryRepository->findById($inventoryId);
            if ($inventory === null) {
                throw new \RuntimeException('Inventory not found');
            }

            if (!$inventory->isAvailable()) {
                throw new \RuntimeException('Inventory is not available');
            }

            if ($inventory->getRemainingImpressions() < $impressions) {
                throw new \RuntimeException('Insufficient inventory');
            }

            $success = $this->inventoryRepository->reserve($inventoryId, $impressions);

            if ($success) {
                // Clear cache
                Cache::tags(['advertising', 'inventory', "publisher:{$inventory->publisher_id}"])->flush();

                // Dispatch event
                $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\InventoryReserved(
                    inventoryId: $inventoryId,
                    impressions: $impressions,
                    publisherId: $inventory->publisher_id,
                    correlationId: $correlationId,
                ));

                $this->logger->info('Inventory reserved successfully', [
                    'correlation_id' => $correlationId,
                    'inventory_id' => $inventoryId,
                    'impressions' => $impressions,
                ]);
            }

            return $success;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Release reserved inventory
     */
    public function releaseInventory(
        int $inventoryId,
        int $impressions,
        ?string $correlationId = null,
    ): bool {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('Releasing inventory', [
            'correlation_id' => $correlationId,
            'inventory_id' => $inventoryId,
            'impressions' => $impressions,
        ]);

        $lockKey = "inventory:{$inventoryId}:lock";
        $lock = Redis::lock($lockKey, self::INVENTORY_LOCK_TTL);

        try {
            $lock->block(5);

            $inventory = $this->inventoryRepository->findById($inventoryId);
            if ($inventory === null) {
                throw new \RuntimeException('Inventory not found');
            }

            $success = $this->inventoryRepository->release($inventoryId, $impressions);

            if ($success) {
                // Clear cache
                Cache::tags(['advertising', 'inventory', "publisher:{$inventory->publisher_id}"])->flush();

                // Dispatch event
                $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\InventoryReleased(
                    inventoryId: $inventoryId,
                    impressions: $impressions,
                    publisherId: $inventory->publisher_id,
                    correlationId: $correlationId,
                ));

                $this->logger->info('Inventory released successfully', [
                    'correlation_id' => $correlationId,
                    'inventory_id' => $inventoryId,
                    'impressions' => $impressions,
                ]);
            }

            return $success;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Get available inventory for RTB
     *
     * @return array<int, array>
     */
    public function getAvailableInventory(
        string $inventoryType,
        string $placement,
        array $targeting = [],
    ): array {
        $cacheKey = "inventory:available:{$inventoryType}:{$placement}:" . md5(json_encode($targeting));

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $inventories = $this->inventoryRepository->findAvailable($inventoryType, $placement, $targeting);

        $result = array_map(
            fn (AdInventory $inv) => [
                'id' => $inv->uuid,
                'inventory_type' => $inv->inventory_type,
                'placement' => $inv->placement,
                'remaining_impressions' => $inv->getRemainingImpressions(),
                'available_from' => $inv->available_from->toIso8601String(),
                'available_until' => $inv->available_until->toIso8601String(),
            ],
            $inventories->all()
        );

        Cache::put($cacheKey, $result, self::INVENTORY_CACHE_TTL);

        return $result;
    }

    /**
     * Forecast inventory availability
     *
     * @return array<string, mixed>
     */
    public function forecastInventory(int $publisherId, int $days = 30): array
    {
        $inventories = $this->inventoryRepository->findByPublisher($publisherId);

        $forecast = [
            'publisher_id' => $publisherId,
            'total_available_impressions' => 0,
            'total_reserved_impressions' => 0,
            'utilization_rate' => 0.0,
            'by_type' => [],
            'daily_forecast' => [],
        ];

        foreach ($inventories as $inventory) {
            if ($inventory->isAvailable()) {
                $forecast['total_available_impressions'] += $inventory->available_impressions;
                $forecast['total_reserved_impressions'] += $inventory->reserved_impressions;

                $type = $inventory->inventory_type;
                if (!isset($forecast['by_type'][$type])) {
                    $forecast['by_type'][$type] = [
                        'available' => 0,
                        'reserved' => 0,
                        'utilization' => 0.0,
                    ];
                }

                $forecast['by_type'][$type]['available'] += $inventory->available_impressions;
                $forecast['by_type'][$type]['reserved'] += $inventory->reserved_impressions;
            }
        }

        // Calculate utilization rate
        if ($forecast['total_available_impressions'] > 0) {
            $forecast['utilization_rate'] = $forecast['total_reserved_impressions'] / $forecast['total_available_impressions'];
        }

        // Calculate per-type utilization
        foreach ($forecast['by_type'] as $type => $data) {
            if ($data['available'] > 0) {
                $forecast['by_type'][$type]['utilization'] = $data['reserved'] / $data['available'];
            }
        }

        // Generate daily forecast (simplified)
        for ($i = 0; $i < $days; $i++) {
            $date = now()->addDays($i)->toDateString();
            $forecast['daily_forecast'][$date] = [
                'estimated_available' => (int) ($forecast['total_available_impressions'] * 0.95), // 95% fill rate assumption
                'estimated_utilization' => $forecast['utilization_rate'],
            ];
        }

        return $forecast;
    }

    /**
     * Create new inventory
     *
     * @throws \RuntimeException
     */
    public function createInventory(
        int $publisherId,
        string $inventoryType,
        string $placement,
        int $availableImpressions,
        \Carbon\Carbon $availableFrom,
        \Carbon\Carbon $availableUntil,
        array $targetingRestrictions = [],
        int $userId = 0,
        ?string $correlationId = null,
    ): AdInventory {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('Creating inventory', [
            'correlation_id' => $correlationId,
            'publisher_id' => $publisherId,
            'inventory_type' => $inventoryType,
            'placement' => $placement,
        ]);

        // Fraud check
        $this->fraudService->check(
            userId: $userId,
            operationType: 'inventory_create',
            amount: 0,
            correlationId: $correlationId,
            context: ['publisher_id' => $publisherId],
        );

        $inventory = AdInventory::create(
            publisherId: $publisherId,
            inventoryType: $inventoryType,
            placement: $placement,
            availableImpressions: $availableImpressions,
            availableFrom: $availableFrom,
            availableUntil: $availableUntil,
            targetingRestrictions: $targetingRestrictions,
            correlationId: $correlationId,
        );

        $savedInventory = $this->inventoryRepository->save($inventory);

        // Clear cache
        Cache::tags(['advertising', 'inventory', "publisher:{$publisherId}"])->flush();

        $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\InventoryCreated(
            inventoryId: $savedInventory->id,
            publisherId: $publisherId,
            correlationId: $correlationId,
        ));

        $this->logger->info('Inventory created successfully', [
            'correlation_id' => $correlationId,
            'inventory_id' => $savedInventory->id,
        ]);

        return $savedInventory;
    }

    /**
     * Get inventory utilization report
     *
     * @return array<string, mixed>
     */
    public function getUtilizationReport(int $publisherId, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $inventories = $this->inventoryRepository->findByPublisher($publisherId);

        $report = [
            'publisher_id' => $publisherId,
            'period' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'total_impressions' => 0,
            'sold_impressions' => 0,
            'revenue' => 0,
            'by_type' => [],
        ];

        foreach ($inventories as $inventory) {
            if ($inventory->available_from->between($from, $to) || $inventory->available_until->between($from, $to)) {
                $type = $inventory->inventory_type;
                $sold = $inventory->reserved_impressions;

                if (!isset($report['by_type'][$type])) {
                    $report['by_type'][$type] = [
                        'total' => 0,
                        'sold' => 0,
                        'revenue' => 0,
                    ];
                }

                $report['by_type'][$type]['total'] += $inventory->available_impressions;
                $report['by_type'][$type]['sold'] += $sold;

                // Estimate revenue (simplified - would need actual pricing data)
                $cpm = match ($type) {
                    'short' => 50000, // 500 RUB per 1000
                    'video' => 80000,
                    'banner' => 30000,
                    'native' => 40000,
                    default => 30000,
                };

                $report['by_type'][$type]['revenue'] += (int) (($sold / 1000) * $cpm);
            }
        }

        // Aggregate totals
        foreach ($report['by_type'] as $type => $data) {
            $report['total_impressions'] += $data['total'];
            $report['sold_impressions'] += $data['sold'];
            $report['revenue'] += $data['revenue'];
        }

        $report['fill_rate'] = $report['total_impressions'] > 0
            ? $report['sold_impressions'] / $report['total_impressions']
            : 0.0;

        return $report;
    }
}
