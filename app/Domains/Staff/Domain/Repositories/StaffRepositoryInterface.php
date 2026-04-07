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


namespace App\Domains\Staff\Domain\Repositories;

use App\Domains\Staff\Domain\DTOs\StaffData;
use App\Domains\Staff\Domain\Entities\Staff;
use Illuminate\Support\Collection;

interface StaffRepositoryInterface
{
    public function create(StaffData $data): Staff;

    public function findById(string $id): ?Staff;

    public function update(string $id, array $data): bool;

    public function delete(string $id): bool;

    public function getByTenant(int $tenantId): Collection;
}
