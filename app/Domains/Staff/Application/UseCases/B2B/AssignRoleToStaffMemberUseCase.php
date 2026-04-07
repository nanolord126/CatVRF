<?php

declare(strict_types=1);

namespace App\Domains\Staff\Application\UseCases\B2B;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Staff\Application\DTO\B2B\AssignRoleDTO;
use App\Domains\Staff\Domain\Events\StaffAssigned;
use App\Domains\Staff\Domain\Repositories\RoleRepositoryInterface;
use App\Domains\Staff\Domain\Repositories\StaffMemberRepositoryInterface;
use App\Services\FraudControlService;
/**
 * Class AssignRoleToStaffMemberUseCase
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Staff\Application\UseCases\B2B
 */
final class AssignRoleToStaffMemberUseCase
{
    public function __construct(private readonly StaffMemberRepositoryInterface $staffMemberRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    public function execute(AssignRoleDTO $dto): void
    {
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        $this->db->transaction(function () use ($dto) {
            $staffMember = $this->staffMemberRepository->find($dto->staffId);
            if (!$staffMember || !$staffMember->getTenantId()->equals($dto->tenantId)) {
                throw new \DomainException('Staff member not found or access denied.');
            }

            $role = $this->roleRepository->find($dto->roleId);
            if (!$role) {
                throw new \DomainException('Role not found.');
            }

            $staffMember->assignRole($role);
            $this->staffMemberRepository->save($staffMember);

            StaffAssigned::dispatch(
                $dto->staffId,
                $dto->roleId,
                $dto->tenantId,
                $dto->correlationId
            );

            $this->logger->info('Role assigned to staff member.', [
                'staff_id' => $dto->staffId->toString(),
                'role_id' => $dto->roleId->toString(),
                'tenant_id' => $dto->tenantId->toString(),
                'correlation_id' => $dto->correlationId,
            ]);
        });
    }
}
