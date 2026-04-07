<?php

declare(strict_types=1);

namespace App\Domains\Staff\Application\UseCases\B2B;

use App\Domains\Staff\Application\DTO\B2B\AddStaffMemberDTO;
use App\Domains\Staff\Domain\Entities\StaffMember;
use App\Domains\Staff\Domain\Enums\StaffStatus;
use App\Domains\Staff\Domain\Events\StaffAssigned;
use App\Domains\Staff\Domain\Repositories\StaffMemberRepositoryInterface;
use App\Domains\Staff\Domain\ValueObjects\StaffId;
use App\Services\FraudControlService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * AddStaffMemberUseCase — B2B Use Case добавления нового сотрудника.
 *
 * Обязательные проверки: FraudControl (через DI) +
 * $this->db->transaction() + audit-лог + доменное событие.
 * Статические фасады $this->logger-> и FraudControlService:: запрещены.
 */
final class AddStaffMemberUseCase
{
    public function __construct(private readonly StaffMemberRepositoryInterface $staffMemberRepository,
        private readonly FraudControlService $fraud,
        private readonly LoggerInterface $auditLogger,
        private readonly CacheRepository $cache,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {

    }

    /**
     * Добавляет нового сотрудника в рамках тенанта.
     *
     * @throws \DomainException Если проверка фрода не пройдена.
     * @throws \RuntimeException Если сохранение не удалось.
     */
    public function execute(AddStaffMemberDTO $dto): StaffId
    {
        $this->fraud->check(
            userId:        $dto->requestedByUserId,
            action:        'staff_member_add',
            amount:        0,
            ip:            $dto->ip,
            fingerprint:   $dto->fingerprint,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): StaffId {
            $staffId = StaffId::generate();

            $staffMember = new StaffMember(
                id:                 $staffId,
                fullName:           $dto->fullName,
                contactInfo:        $dto->contactInfo,
                status:             StaffStatus::ACTIVE,
                tenantId:           $dto->tenantId,
                vertical:           $dto->vertical,
                verticalResourceId: $dto->verticalResourceId,
                businessGroupId:    $dto->businessGroupId,
            );

            $this->staffMemberRepository->save($staffMember);

            StaffAssigned::dispatch(
                $staffId,
                Uuid::uuid4(),
                $dto->tenantId,
                $dto->correlationId,
            );

            $this->invalidateCache($dto->tenantId->toString());

            $this->auditLogger->info('New staff member added.', [
                'staff_id'       => $staffId->toString(),
                'tenant_id'      => $dto->tenantId->toString(),
                'vertical'       => $dto->vertical->value,
                'correlation_id' => $dto->correlationId,
            ]);

            return $staffId;
        });
    }

    /**
     * Инвалидирует кэш списка сотрудников тенанта.
     */
    private function invalidateCache(string $tenantId): void
    {
        $this->cache->forget("staff:list:tenant:{$tenantId}");
    }
}
