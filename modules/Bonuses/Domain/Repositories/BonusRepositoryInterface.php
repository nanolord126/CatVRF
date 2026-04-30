<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Repositories;

use Modules\Bonuses\Domain\Entities\BonusAggregate;
use Modules\Bonuses\Domain\Entities\BonusProgram;
use Modules\Bonuses\Domain\Enums\BonusStatus;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\ValueObjects\LoyaltyStatus;
use DateTimeImmutable;

/**
 * Interface BonusRepositoryInterface
 *
 * Defines strictly isolated persistence boundary mapping domain aggregates effectively transparently.
 * Operates purely enforcing the Data Mapper structurally rejecting leaking external complexities natively.
 * All repository operations must be atomic, consistent, and auditable for compliance requirements.
 */
interface BonusRepositoryInterface
{
    /**
     * Resolves uniquely bounded specific mapping instance traversing aggregate domains absolutely safely.
     *
     * @param  string  $id  Target specific scalar distinct identifier mapping sequence purely.
     * @return BonusAggregate|null
     */
    public function findById(string $id): ?BonusAggregate;

    /**
     * Resolves currently active uniquely bounded specific mapping instances for specific owner inherently tracking sequences purely.
     * Results are ordered by consumption priority (promotional first, then action, etc.) and expiration date.
     *
     * @param  string  $ownerId  Target specific owner matching actively bounds strictly inherently.
     * @param  BonusType|null  $type  Optional filter by bonus type.
     * @return BonusAggregate[]
     */
    public function findActiveByOwnerId(string $ownerId, ?BonusType $type = null): array;

    /**
     * Resolves all bonuses for a specific owner regardless of status.
     * Used for analytics, reporting, and comprehensive balance calculations.
     *
     * @param  string  $ownerId  Target specific owner.
     * @param  BonusStatus|null  $status  Optional filter by status.
     * @return BonusAggregate[]
     */
    public function findByOwnerId(string $ownerId, ?BonusStatus $status = null): array;

    /**
     * Finds bonuses that are expiring within the given number of days.
     * Used for scheduled expiration jobs and user notifications.
     *
     * @param  int  $daysWithin  Number of days to look ahead.
     * @param  BonusStatus  $status  Status to filter (typically ACTIVE).
     * @return BonusAggregate[]
     */
    public function findExpiringWithinDays(int $daysWithin, BonusStatus $status = BonusStatus::ACTIVE): array;

    /**
     * Finds bonuses that have expired but not yet marked as expired.
     * Used for bulk expiration operations by scheduled jobs.
     *
     * @param  DateTimeImmutable  $before  The cutoff datetime.
     * @return BonusAggregate[]
     */
    public function findExpiredBefore(DateTimeImmutable $before): array;

    /**
     * Finds bonuses in FROZEN status for review.
     * Used for fraud investigation workflows.
     *
     * @param  int  $limit  Maximum number of results to return.
     * @return BonusAggregate[]
     */
    public function findFrozen(int $limit = 100): array;

    /**
     * Persists newly structured bounded limits applying transactions natively effectively transparently safely.
     * Must handle both insert and update operations idempotently.
     *
     * @param  BonusAggregate  $bonus  Structured strictly modeled bounds mapping correctly cleanly.
     */
    public function save(BonusAggregate $bonus): void;

    /**
     * Persists multiple bonuses in a single transaction for performance.
     * Used for bulk operations and batch processing.
     *
     * @param  BonusAggregate[]  $bonuses  Array of bonus aggregates to persist.
     */
    public function saveMany(array $bonuses): void;

    /**
     * Applies locking constraints bounding actively resolving transaction mechanisms uniquely purely natively.
     * Uses pessimistic locking to prevent race conditions during consumption.
     *
     * @param  string  $id  Specific bounded target tracking locking internally effectively strictly.
     * @return BonusAggregate|null
     */
    public function lockById(string $id): ?BonusAggregate;

    /**
     * Locks multiple bonuses by their IDs in a single operation.
     * Used for bulk consumption operations.
     *
     * @param  string[]  $ids  Array of bonus IDs to lock.
     * @return BonusAggregate[]
     */
    public function lockByIds(array $ids): array;

    /**
     * Calculates the total available balance for an owner.
     * Only includes ACTIVE bonuses that are not expired.
     *
     * @param  string  $ownerId  Target specific owner.
     * @return int Total balance in smallest currency unit.
     */
    public function getAvailableBalance(string $ownerId): int;

    /**
     * Calculates the total balance by bonus type for an owner.
     * Used for analytics and reporting.
     *
     * @param  string  $ownerId  Target specific owner.
     * @return array Array mapping bonus types to balances.
     */
    public function getBalanceByType(string $ownerId): array;

    /**
     * Counts bonuses by status for analytics and monitoring.
     *
     * @param  BonusStatus  $status  The status to count.
     * @return int The count of bonuses with the given status.
     */
    public function countByStatus(BonusStatus $status): int;

    /**
     * Finds bonuses by correlation ID for tracing and debugging.
     *
     * @param  string  $correlationId  The correlation ID to search for.
     * @return BonusAggregate[]
     */
    public function findByCorrelationId(string $correlationId): array;

    /**
     * Finds bonuses by source entity for tracking and reconciliation.
     *
     * @param  string  $sourceId  The source entity ID.
     * @param  string  $sourceType  The source entity type.
     * @return BonusAggregate[]
     */
    public function findBySource(string $sourceId, string $sourceType): array;

    /**
     * Deletes a bonus aggregate from persistence.
     * Should only be used for cleanup operations, not for soft deletes.
     *
     * @param  string  $id  The bonus ID to delete.
     */
    public function delete(string $id): void;

    /**
     * Performs a paginated search with filters.
     * Used for admin interfaces and reporting.
     *
     * @param  array  $filters  Associative array of filters (owner_id, type, status, etc.).
     * @param  int  $page  The page number (1-indexed).
     * @param  int  $perPage  Items per page.
     * @return array Array containing 'data' and 'pagination' keys.
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 50): array;
}

/**
 * Interface BonusProgramRepositoryInterface
 *
 * Defines persistence operations for bonus program configurations.
 * Programs are templates/blueprints for creating individual bonus aggregates.
 */
interface BonusProgramRepositoryInterface
{
    /**
     * Finds a program by its unique identifier.
     *
     * @param  string  $id  The program ID.
     * @return BonusProgram|null
     */
    public function findById(string $id): ?BonusProgram;

    /**
     * Finds a program by its unique code.
     *
     * @param  string  $code  The program code.
     * @return BonusProgram|null
     */
    public function findByCode(string $code): ?BonusProgram;

    /**
     * Finds all programs that are currently active.
     *
     * @param  DateTimeImmutable  $now  The current datetime for active check.
     * @return BonusProgram[]
     */
    public function findActive(DateTimeImmutable $now = new DateTimeImmutable()): array;

    /**
     * Finds programs by bonus type.
     *
     * @param  BonusType  $type  The bonus type.
     * @return BonusProgram[]
     */
    public function findByType(BonusType $type): array;

    /**
     * Saves a program configuration.
     *
     * @param  BonusProgram  $program  The program to save.
     */
    public function save(BonusProgram $program): void;

    /**
     * Deletes a program configuration.
     *
     * @param  string  $id  The program ID to delete.
     */
    public function delete(string $id): void;
}

/**
 * Interface LoyaltyStatusRepositoryInterface
 *
 * Defines persistence operations for loyalty status tracking.
 * Loyalty status is separate from bonus aggregates to enable centralized tier management.
 */
interface LoyaltyStatusRepositoryInterface
{
    /**
     * Finds the loyalty status for a specific owner.
     *
     * @param  string  $ownerId  The owner identifier.
     * @return LoyaltyStatus|null
     */
    public function findByOwnerId(string $ownerId): ?LoyaltyStatus;

    /**
     * Saves the loyalty status for an owner.
     *
     * @param  string  $ownerId  The owner identifier.
     * @param  LoyaltyStatus  $status  The loyalty status to save.
     */
    public function save(string $ownerId, LoyaltyStatus $status): void;

    /**
     * Finds all owners at a specific loyalty tier.
     * Used for tier-based marketing and benefits distribution.
     *
     * @param  string  $tier  The loyalty tier.
     * @param  int  $limit  Maximum number of results.
     * @return array Array of owner IDs.
     */
    public function findOwnersByTier(string $tier, int $limit = 1000): array;

    /**
     * Resets monthly point tracking for all owners.
     * Called by scheduled jobs at the start of each month.
     *
     * @return int Number of records updated.
     */
    public function resetMonthlyTracking(): int;
}
