<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Repositories;

use Modules\Fitness\Domain\Corporate\Entities\EmployeeMembership;

interface EmployeeMembershipRepositoryInterface
{
    public function save(EmployeeMembership $membership): EmployeeMembership;

    public function findById(int $id): ?EmployeeMembership;

    public function findByCorporateEnrollmentId(int $corporateEnrollmentId): array;

    public function findByClientId(int $clientId): array;

    public function findActiveByCorporateEnrollmentId(int $corporateEnrollmentId): array;

    public function findActiveByClientId(int $clientId): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
