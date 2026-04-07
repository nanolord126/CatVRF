<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Analytics\Domain\Interfaces;

use App\Domains\Analytics\Domain\Entities\AnalyticsEvent;
use Illuminate\Support\Collection;

interface AnalyticsEventRepositoryInterface
{
    public function save(AnalyticsEvent $event): void;

    /**
     * @param AnalyticsEvent[] $events
     */
    public function saveBulk(array $events): void;

    public function getAggregatedData(int $tenantId, string $metric, \DateTime $from, \DateTime $to, string $groupBy): Collection;
}
