<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Repositories;

use Modules\Analytics\Domain\Entities\BehavioralEvent;
use Modules\Analytics\Domain\ValueObjects\UserId;
use Modules\Analytics\Domain\ValueObjects\Timestamp;

/**
 * Behavioral Event Repository Interface
 *
 * Defines the contract for accessing behavioral event data.
 * This is a Domain layer interface - the implementation is in Infrastructure.
 *
 * The repository follows the Repository pattern from Domain-Driven Design,
 * providing an abstraction over data storage. This allows the domain layer
 * to remain independent of infrastructure concerns.
 *
 * @package Modules\Analytics\Domain\Repositories
 */
interface BehavioralEventRepositoryInterface
{
    /**
     * Find events by user ID.
     *
     * Returns the most recent events for a specific user, ordered by occurrence time.
     * Useful for analyzing user behavior patterns and recent activity.
     *
     * @param int $userId The user ID to search for
     * @param int $limit Maximum number of events to return (default: 100)
     * @return BehavioralEvent[] Array of behavioral events
     */
    public function findByUserId(int $userId, int $limit = 100): array;

    /**
     * Find events by event type.
     *
     * Returns events of a specific type across all users, useful for
     * analyzing event patterns and trends.
     *
     * @param string $eventType The event type to search for (e.g., 'page_view', 'click', 'purchase')
     * @param int $limit Maximum number of events to return (default: 100)
     * @return BehavioralEvent[] Array of behavioral events
     */
    public function findByEventType(string $eventType, int $limit = 100): array;

    /**
     * Find events by user ID and event type.
     *
     * Returns events of a specific type for a specific user.
     *
     * @param int $userId The user ID to search for
     * @param string $eventType The event type to search for
     * @param int $limit Maximum number of events to return (default: 100)
     * @return BehavioralEvent[] Array of behavioral events
     */
    public function findByUserIdAndEventType(int $userId, string $eventType, int $limit = 100): array;

    /**
     * Find events within a date range.
     *
     * Returns all events that occurred between the specified dates.
     *
     * @param Timestamp $from Start of the date range
     * @param Timestamp $to End of the date range
     * @param int|null $tenantId Optional tenant ID for multi-tenancy
     * @return BehavioralEvent[] Array of behavioral events
     */
    public function findByDateRange(Timestamp $from, Timestamp $to, ?int $tenantId = null): array;

    /**
     * Get aggregated user statistics for RFM analysis.
     *
     * Returns aggregated statistics for all users in a tenant, including:
     * - Last purchase date
     * - Purchase frequency count
     * - Total monetary value
     *
     * @param int $tenantId The tenant ID to aggregate statistics for
     * @return array Array of user statistics keyed by user_id
     */
    public function getUserStatsForRFM(int $tenantId): array;

    /**
     * Find the last purchase event for a user.
     *
     * Used in RFM analysis to calculate recency score.
     *
     * @param UserId $userId The user ID
     * @return BehavioralEvent|null The last purchase event or null if not found
     */
    public function findLastPurchase(UserId $userId): ?BehavioralEvent;

    /**
     * Count the number of purchases for a user within a period.
     *
     * Used in RFM analysis to calculate frequency score.
     *
     * @param UserId $userId The user ID
     * @param int $days Number of days to look back (default: 90)
     * @return int Number of purchases
     */
    public function countPurchases(UserId $userId, int $days = 90): int;

    /**
     * Calculate total spend for a user within a period.
     *
     * Used in RFM analysis to calculate monetary score.
     *
     * @param UserId $userId The user ID
     * @param int $days Number of days to look back (default: 90)
     * @return float Total monetary value spent
     */
    public function totalSpend(UserId $userId, int $days = 90): float;

    /**
     * Save a behavioral event.
     *
     * Persists a new behavioral event to the data store.
     *
     * @param BehavioralEvent $event The behavioral event to save
     * @return void
     */
    public function save(BehavioralEvent $event): void;

    /**
     * Save multiple behavioral events in a batch.
     *
     * Useful for bulk inserts to improve performance.
     *
     * @param BehavioralEvent[] $events Array of behavioral events to save
     * @return void
     */
    public function saveBatch(array $events): void;

    /**
     * Delete events older than specified date.
     *
     * Used for data retention policies to clean up old events.
     *
     * @param \DateTimeImmutable $beforeDate Delete events before this date
     * @return int Number of deleted events
     */
    public function deleteOlderThan(\DateTimeImmutable $beforeDate): int;

    /**
     * Count events by type for a tenant.
     *
     * Returns a breakdown of event types and their counts.
     *
     * @param int $tenantId The tenant ID
     * @return array Array of event type counts
     */
    public function countByEventType(int $tenantId): array;

    /**
     * Get event trends over time.
     *
     * Returns aggregated event counts grouped by time period.
     *
     * @param Timestamp $from Start of the date range
     * @param Timestamp $to End of the date range
     * @param string $groupBy Grouping period: 'hour', 'day', 'week', 'month'
     * @param int|null $tenantId Optional tenant ID
     * @return array Array of trend data
     */
    public function getEventTrends(Timestamp $from, Timestamp $to, string $groupBy = 'day', ?int $tenantId = null): array;

    /**
     * Find events by entity.
     *
     * Returns all events related to a specific entity (e.g., product, service).
     *
     * @param string $entityType The type of entity
     * @param int $entityId The ID of the entity
     * @param int $limit Maximum number of events to return
     * @return BehavioralEvent[] Array of behavioral events
     */
    public function findByEntity(string $entityType, int $entityId, int $limit = 100): array;

    /**
     * Get top events by frequency for a tenant.
     *
     * Returns the most frequently occurring event types.
     *
     * @param int $tenantId The tenant ID
     * @param int $limit Maximum number of event types to return
     * @return array Array of event type frequencies
     */
    public function getTopEventTypes(int $tenantId, int $limit = 10): array;
}
