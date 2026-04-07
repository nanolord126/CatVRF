<?php

declare(strict_types=1);

namespace App\Domains\Staff\Application\UseCases\B2B;

use App\Domains\Staff\Application\DTO\B2B\UpdateScheduleDTO;
use App\Domains\Staff\Domain\Entities\Schedule;
use App\Domains\Staff\Domain\Events\StaffScheduleChanged;
use App\Domains\Staff\Domain\Repositories\ScheduleRepositoryInterface;
use App\Domains\Staff\Domain\Repositories\StaffMemberRepositoryInterface;
use App\Domains\Staff\Domain\ValueObjects\ScheduleId;
use App\Services\FraudControlService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * UpdateStaffScheduleUseCase — B2B Use Case обновления расписания сотрудника.
 *
 * Проверяет принадлежность сотрудника тенанту,
 * выполняет fraud-чек, диспатчит доменное событие и логирует в audit.
 */
final class UpdateStaffScheduleUseCase
{
    public function __construct(private readonly StaffMemberRepositoryInterface $staffMemberRepository,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
        private readonly FraudControlService $fraud,
        private readonly LoggerInterface $auditLogger,
        private readonly CacheRepository $cache,
        private readonly \Illuminate\Database\DatabaseManager $db) {

    }

    /**
     * Обновляет расписание сотрудника.
     *
     * @throws \DomainException Если сотрудник не найден или не принадлежит тенанту.
     * @throws \RuntimeException Если расписание не сохранено.
     */
    public function execute(UpdateScheduleDTO $dto): ScheduleId
    {
        $this->fraud->check(
            userId:        $dto->requestedByUserId,
            action:        'staff_schedule_update',
            amount:        0,
            ip:            $dto->ip,
            fingerprint:   $dto->fingerprint,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): ScheduleId {
            $staffMember = $this->staffMemberRepository->find($dto->staffId);

            if ($staffMember === null || ! $staffMember->getTenantId()->equals($dto->tenantId)) {
                throw new \DomainException(
                    sprintf(
                        'Сотрудник %s не найден или доступ запрещён.',
                        $dto->staffId->toString(),
                    )
                );
            }

            $scheduleId = ScheduleId::generate();

            $schedule = new Schedule(
                id:        $scheduleId,
                staffId:   $dto->staffId,
                startTime: $dto->startTime,
                endTime:   $dto->endTime,
            );

            $this->scheduleRepository->save($schedule);

            StaffScheduleChanged::dispatch(
                $dto->staffId,
                $scheduleId,
                $dto->tenantId,
                $dto->correlationId,
            );

            $this->invalidateCache($dto->tenantId->toString(), $dto->staffId->toString());

            $this->auditLogger->info('Staff schedule updated.', [
                'staff_id'       => $dto->staffId->toString(),
                'schedule_id'    => $scheduleId->toString(),
                'tenant_id'      => $dto->tenantId->toString(),
                'correlation_id' => $dto->correlationId,
            ]);

            return $scheduleId;
        });
    }

    /**
     * Инвалидирует кэши расписания для тенанта и конкретного сотрудника.
     */
    private function invalidateCache(string $tenantId, string $staffId): void
    {
        $this->cache->forget("staff:schedule:tenant:{$tenantId}:member:{$staffId}");
        $this->cache->forget("staff:list:tenant:{$tenantId}");
    }
}
