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


namespace App\Domains\Food\Domain\Repositories;

use App\Domains\Food\Domain\Entities\Order;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    public function findById(Uuid $id): ?Order;

    public function findByClientId(Uuid $clientId): Collection;

    public function save(Order $order): void;

    /**
     * @param array<string, mixed> $criteria
     * @return Collection<Order>
     */
    public function search(array $criteria): Collection;
}
