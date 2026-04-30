<?php

declare(strict_types=1);

namespace App\Domains\Logistics\DTOs;

/**
 * Batch Optimization Result DTO
 *
 * Result from SpitBatchOptimizerService for Agentic AI.
 */
final readonly class BatchOptimizationResult
{
    /**
     * @param  array<int, array{order_ids: array, order_count: int, total_weight_kg: float, total_distance_km: float, deadhead_ratio: float, route: array, is_shadow_mode: bool}>  $batches
     */
    public function __construct(
        public array $batches,
        public int $totalOrders,
        public float $totalDeadheadRatio,
        public bool $isResortSpit,
        public float $optimizationScore,
    ) {}
}
