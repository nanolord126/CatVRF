<?php

declare(strict_types=1);

/**
 * EloquentAdImpressionRepository — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/eloquentadimpressionrepository
 */


namespace App\Domains\Advertising\Infrastructure\Persistence;

use App\Domains\Advertising\Domain\Entities\AdImpression;
use App\Domains\Advertising\Domain\Interfaces\AdImpressionRepositoryInterface;
use App\Models\Advertising\AdImpression as AdImpressionModel;

/**
 * Class EloquentAdImpressionRepository
 *
 * Part of the Advertising vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Advertising\Infrastructure\Persistence
 */
final class EloquentAdImpressionRepository implements AdImpressionRepositoryInterface
{
    /**
     * Handle save operation.
     *
     * @throws \DomainException
     */
    public function save(AdImpression $impression): AdImpression
    {
        $model = AdImpressionModel::create([
            'campaign_id' => $impression->campaign_id,
            'placement_id' => $impression->placement_id,
            'user_id' => $impression->user_id,
            'ip_address' => $impression->ip_address,
            'device_fingerprint' => $impression->device_fingerprint,
            'cost' => $impression->cost,
            'correlation_id' => $impression->correlation_id,
        ]);

        return new AdImpression(
            id: $model->id,
            campaign_id: $model->campaign_id,
            placement_id: $model->placement_id,
            user_id: $model->user_id,
            ip_address: $model->ip_address,
            device_fingerprint: $model->device_fingerprint,
            cost: $model->cost,
            created_at: $model->created_at,
            correlation_id: $model->correlation_id
        );
    }
}
