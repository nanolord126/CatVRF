<?php

declare(strict_types=1);

namespace Modules\Promo\Domain\Repositories;

use Modules\Promo\Domain\Entities\PromoCampaign;

/**
 * Interface PromoRepositoryInterface
 *
 * Defines the strict, abstract persistence boundary for the Promo aggregate.
 * Enforces the Dependency Inversion principle by allowing the Application layer
 * to persist and retrieve state without coupling to the underlying storage mechanism.
 */
interface PromoRepositoryInterface
{
    /**
     * Resolves a PromoCampaign by its unique textual code (e.g., 'SUMMER50').
     *
     * @param string $code The unique alphanumeric code representing the mapped campaign.
     * @return PromoCampaign|null Returns the reconstituted aggregate, or natively null if definitively absent.
     */
    public function findByCode(string $code): ?PromoCampaign;

    /**
     * Obtains an exclusive explicit pessimistic lock on the campaign record.
     * Crucial for preventing concurrent aggregate exhaustions and enforcing total budget structural limits securely.
     *
     * @param string $code The strictly formatted string identifier mapping the bound record.
     * @return PromoCampaign|null Returns identically locked aggregated states safely cleanly.
     */
    public function lockByCode(string $code): ?PromoCampaign;

    /**
     * Translates and natively persists the deeply structured state of a bounded PromoCampaign aggregate
     * seamlessly into the physical infrastructure layer.
     *
     * @param PromoCampaign $campaign The internally consistent validated domain model mapped securely.
     * @return void
     */
    public function save(PromoCampaign $campaign): void;
}
