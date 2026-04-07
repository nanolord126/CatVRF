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


namespace App\Domains\Delivery\Domain\Repositories;

use App\Domains\Delivery\Domain\DTOs\DeliveryData;
use App\Domains\Delivery\Domain\Entities\Delivery;
use Illuminate\Support\Collection;

interface DeliveryRepositoryInterface
{
    public function create(DeliveryData $data): Delivery;

    public function findById(string $id): ?Delivery;

    public function update(string $id, array $data): bool;

    public function delete(string $id): bool;

    public function getByStatus(string $status): Collection;

    public function assignCourier(string $deliveryId, int $courierId): bool;
}
