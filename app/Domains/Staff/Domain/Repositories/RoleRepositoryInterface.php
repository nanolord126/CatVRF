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

use App\Domains\Staff\Domain\Entities\Role;
use App\Domains\Staff\Domain\ValueObjects\RoleId;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function find(RoleId $id): ?Role;

    public function findByName(string $name): ?Role;

    public function all(): Collection;

    public function save(Role $role): void;
}
