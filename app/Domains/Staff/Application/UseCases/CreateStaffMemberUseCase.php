<?php

declare(strict_types=1);

namespace App\Domains\Staff\Application\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Staff\Domain\DTOs\StaffData;
use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Repositories\StaffRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

/**
 * Class CreateStaffMemberUseCase
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
final class CreateStaffMemberUseCase
{
    public function __construct(private readonly StaffRepositoryInterface $staffRepository,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    public function __invoke(StaffData $staffData): Staff
    {
        $correlationId = $staffData->correlation_id ?? Str::uuid()->toString();

        $this->logger->info('Creating staff member', [
            'correlation_id' => $correlationId,
            'tenant_id' => $staffData->tenant_id,
            'user_id' => $staffData->user_id,
            'role' => $staffData->role->value,
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($staffData, $correlationId) {
            return $this->staffRepository->create(
                new StaffData(
                    user_id: $staffData->user_id,
                    tenant_id: $staffData->tenant_id,
                    role: $staffData->role,
                    correlation_id: $correlationId
                )
            );
        });
    }
}
