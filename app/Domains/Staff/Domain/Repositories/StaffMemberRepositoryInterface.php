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

use App\Domains\Staff\Domain\Entities\StaffMember;
use App\Domains\Staff\Domain\ValueObjects\StaffId;
use Illuminate\Support\Collection;
use Ramsey\Uuid\UuidInterface;

interface StaffMemberRepositoryInterface
{
    public function find(StaffId $id): ?StaffMember;

    public function findByTenant(UuidInterface $tenantId): Collection;

    public function save(StaffMember $staffMember): void;

    public function delete(StaffId $id): void;
}
