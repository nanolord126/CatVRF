<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Repositories;

use Modules\Analytics\Domain\Entities\BehavioralEvent;

/**
 * Behavioral Event Repository Interface
 *
 * Defines the contract for accessing behavioral event data.
 * This is a Domain layer interface - the implementation is in Infrastructure.
 */
interface BehavioralEventRepositoryInterface
{
    /**
     * Find events by user ID.
     *
     * @param int $userId
     * @param int $limit
     * @return BehavioralEvent[]
     */
    public function findByUserId(int $userId, int $limit = 100): array;

    /**
     * Find events by event type.
     *
     * @param string $eventType
     * @param int $limit
     * @return BehavioralEvent[]
     */
    public function findByEventType(string $eventType, int $limit = 100): array;

    /**
     * Get aggregated user statistics for RFM analysis.
     *
     * @param int $tenantId
     * @return array
     */
    public function getUserStatsForRFM(int $tenantId): array;

    /**
     * Save a behavioral event.
     *
     * @param DomainBehavioralEvent $event
     * @return void
     */
    public function save(DomainBehavioralEvent $event): void;

    /**
     * Delete events older than specified date.
     *
     * @param \DateTimeImmutable $beforeDate
     * @return int Number of deleted events
     */
    public function deleteOlderThan(\DateTimeImmutable $beforeDate): int;
}
