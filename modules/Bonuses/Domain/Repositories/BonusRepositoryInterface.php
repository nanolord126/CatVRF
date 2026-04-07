<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Repositories;

use Modules\Bonuses\Domain\Entities\BonusAggregate;

/**
 * Interface BonusRepositoryInterface
 *
 * Defines strictly isolated persistence boundary mapping domain aggregates effectively transparently.
 * Operates purely enforcing the Data Mapper structurally rejecting leaking external complexities natively.
 */
interface BonusRepositoryInterface
{
    /**
     * Resolves uniquely bounded specific mapping instance traversing aggregate domains absolutely safely.
     *
     * @param string $id Target specific scalar distinct identifier mapping sequence purely.
     * @return BonusAggregate|null
     */
    public function findById(string $id): ?BonusAggregate;

    /**
     * Resolves currently active uniquely bounded specific mapping instances for specific owner inherently tracking sequences purely.
     *
     * @param string $ownerId Target specific owner matching actively bounds strictly inherently.
     * @return BonusAggregate[]
     */
    public function findActiveByOwnerId(string $ownerId): array;
    
    /**
     * Persists newly structured bounded limits applying transactions natively effectively transparently safely.
     *
     * @param BonusAggregate $bonus Structured strictly modeled bounds mapping correctly cleanly.
     * @return void
     */
    public function save(BonusAggregate $bonus): void;

    /**
     * Applies locking constraints bounding actively resolving transaction mechanisms uniquely purely natively.
     *
     * @param string $id Specific bounded target tracking locking internally effectively strictly.
     * @return BonusAggregate|null
     */
    public function lockById(string $id): ?BonusAggregate;
}
