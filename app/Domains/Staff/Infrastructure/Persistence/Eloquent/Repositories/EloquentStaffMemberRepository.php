<?php

declare(strict_types=1);

namespace App\Domains\Staff\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Staff\Domain\Entities\StaffMember;
use App\Domains\Staff\Domain\Repositories\StaffMemberRepositoryInterface;
use App\Domains\Staff\Domain\ValueObjects\ContactInfo;
use App\Domains\Staff\Domain\ValueObjects\FullName;
use App\Domains\Staff\Domain\ValueObjects\StaffId;
use App\Domains\Staff\Infrastructure\Persistence\Eloquent\Models\StaffMemberModel;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Ramsey\Uuid\UuidInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use App\Services\FraudControlService;

/**
 * EloquentStaffMemberRepository — реализация StaffMemberRepositoryInterface
 * через Eloquent.
 *
 * Использует $this->db->transaction() для мутаций, кэш Redis для чтения
 * и audit-логирование через LoggerInterface (без статических фасадов).
 */
final class EloquentStaffMemberRepository implements StaffMemberRepositoryInterface
{
    private const CACHE_TTL_SECONDS = 300;
    private const CACHE_PREFIX      = 'staff:member:';

    public function __construct(private readonly CacheRepository $cache,
        private readonly LoggerInterface $auditLogger,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db) {

    }

    /**
     * Сохраняет (создаёт или обновляет) доменный объект StaffMember в БД.
     *
     * @throws \RuntimeException При ошибке сохранения.
     */
    public function save(StaffMember $staffMember): void
    {
        $this->db->transaction(function () use ($staffMember): void {
            StaffMemberModel::updateOrCreate(
                ['id' => $staffMember->getId()->toString()],
                [
                    'uuid'                => $staffMember->getId()->toString(),
                    'tenant_id'           => $staffMember->getTenantId()->toString(),
                    'business_group_id'   => $staffMember->getBusinessGroupId()?->toString(),
                    'first_name'          => $staffMember->getFullName()->firstName,
                    'last_name'           => $staffMember->getFullName()->lastName,
                    'middle_name'         => $staffMember->getFullName()->middleName,
                    'email'               => $staffMember->getContactInfo()->email,
                    'phone'               => $staffMember->getContactInfo()->phone,
                    'status'              => $staffMember->getStatus()->value,
                    'vertical'            => $staffMember->getVertical()->value,
                    'vertical_resource_id'=> $staffMember->getVerticalResourceId()?->toString(),
                    'correlation_id'      => Uuid::uuid4()->toString(),
                ],
            );

            $this->invalidateCache($staffMember->getId()->toString());

            $this->auditLogger->info('StaffMember saved.', [
                'staff_id'  => $staffMember->getId()->toString(),
                'tenant_id' => $staffMember->getTenantId()->toString(),
            ]);
        });
    }

    /**
     * Ищет доменный StaffMember по StaffId.
     * Кэширует результат на 300 секунд.
     *
     * @return StaffMember|null null если не найден.
     */
    public function find(StaffId $staffId): ?StaffMember
    {
        $cacheKey = self::CACHE_PREFIX . $staffId->toString();

        $model = $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn (): ?StaffMemberModel => StaffMemberModel::find($staffId->toString()),
        );

        if ($model === null) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        return $this->hydrate($model);
    }

    /**
     * Возвращает всех сотрудников тенанта.
     *
     * @return Collection<int, StaffMember>
     */
    public function findByTenant(UuidInterface $tenantId): Collection
    {
        $models = StaffMemberModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId->toString())
            ->orderBy('last_name')
            ->get();

        return $models->map(fn (StaffMemberModel $m): StaffMember => $this->hydrate($m));
    }

    /**
     * Удаляет (soft-delete) сотрудника по StaffId.
     *
     * @throws \DomainException Если сотрудник не найден.
     */
    public function delete(StaffId $staffId): void
    {
        $this->db->transaction(function () use ($staffId): void {
            $model = StaffMemberModel::findOrFail($staffId->toString());
            $model->delete();

            $this->invalidateCache($staffId->toString());

            $this->auditLogger->info('StaffMember deleted.', [
                'staff_id' => $staffId->toString(),
            ]);
        });
    }

    /**
     * Гидрирует Eloquent-модель в доменный объект StaffMember.
     */
    private function hydrate(StaffMemberModel $model): StaffMember
    {
        return new StaffMember(
            id:                 new StaffId(Uuid::fromString($model->id)),
            fullName:           new FullName(
                firstName:  $model->first_name,
                lastName:   $model->last_name,
                middleName: $model->middle_name,
            ),
            contactInfo:        new ContactInfo(
                email: $model->email,
                phone: $model->phone,
            ),
            status:             $model->status,
            tenantId:           Uuid::fromString($model->tenant_id),
            vertical:           $model->vertical,
            verticalResourceId: $model->vertical_resource_id !== null
                ? Uuid::fromString($model->vertical_resource_id)
                : null,
            businessGroupId:    $model->business_group_id !== null
                ? Uuid::fromString($model->business_group_id)
                : null,
        );
    }

    /**
     * Инвалидирует кэш для конкретного сотрудника.
     */
    private function invalidateCache(string $staffId): void
    {
        $this->cache->forget(self::CACHE_PREFIX . $staffId);
    }
}
