<?php

declare(strict_types=1);

namespace App\Domains\Staff\Application\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Domains\Staff\Domain\Repositories\StaffRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

/**
 * Class UpdateStaffMemberRoleUseCase
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
 * @package App\Domains\Staff\Application\UseCases
 */
final class UpdateStaffMemberRoleUseCase
{
    public function __construct(private readonly StaffRepositoryInterface $staffRepository,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    /**
     * Handle __invoke operation.
     *
     * @throws \DomainException
     */
    public function __invoke(string $staffId, StaffRole $role, ?string $correlationId = null): Staff
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->logger->info('Updating staff member role', [
            'correlation_id' => $correlationId,
            'staff_id' => $staffId,
            'new_role' => $role->value,
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($staffId, $role) {
            $this->staffRepository->update($staffId, ['role' => $role]);
            return $this->staffRepository->findById($staffId);
        });
    }
}
