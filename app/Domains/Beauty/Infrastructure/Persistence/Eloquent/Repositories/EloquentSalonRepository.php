<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Beauty\Domain\Entities\Salon;
use App\Domains\Beauty\Domain\Repositories\SalonRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers\SalonMapper;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon as EloquentSalon;
use App\Shared\Domain\ValueObjects\TenantId;
use Illuminate\Support\Collection;

/**
 * Eloquent-реализация репозитория салонов красоты.
 *
 * Все запросы используют глобальный scope из BeautySalon::booted() (фильтрация по tenant_id).
 *
 * @package App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories
 */
final class EloquentSalonRepository implements SalonRepositoryInterface
{
    public function __construct(
        private EloquentSalon $eloquentSalonModel,
        private SalonMapper   $salonMapper,
    ) {}

    /**
     * Найти салон по UUID-идентификатору.
     */
    public function findById(SalonId $id): ?Salon
    {
        $eloquentSalon = $this->eloquentSalonModel
            ->where('uuid', $id->getValue())
            ->first();

        return $eloquentSalon ? $this->salonMapper->toDomain($eloquentSalon) : null;
    }

    /**
     * Получить все салоны тенанта.
     */
    public function findByTenantId(TenantId $tenantId): Collection
    {
        return $this->eloquentSalonModel
            ->where('tenant_id', $tenantId->getValue())
            ->get()
            ->map(fn (EloquentSalon $s) => $this->salonMapper->toDomain($s));
    }

    /**
     * Поиск салонов по городу / названию и опциональному фильтру рейтинга.
     *
     * @param array<string, mixed> $criteria  город, название, min_rating, is_verified
     */
    public function search(array $criteria): Collection
    {
        $query = $this->eloquentSalonModel->newQuery();

        if (isset($criteria['city'])) {
            $city = $criteria['city'];
            $query->where(function ($q) use ($city): void {
                $q->where('address_full', 'like', "%{$city}%")
                  ->orWhere('name', 'like', "%{$city}%");
            });
        }

        if (isset($criteria['min_rating'])) {
            $query->where('rating', '>=', $criteria['min_rating']);
        }

        if (isset($criteria['is_verified'])) {
            $query->where('is_verified', (bool) $criteria['is_verified']);
        }

        return $query
            ->orderByDesc('rating')
            ->get()
            ->map(fn (EloquentSalon $s) => $this->salonMapper->toDomain($s));
    }

    /**
     * Получить количество салонов тенанта.
     */
    public function countByTenantId(TenantId $tenantId): int
    {
        return $this->eloquentSalonModel
            ->where('tenant_id', $tenantId->getValue())
            ->count();
    }

    /**
     * Сохранить салон через mapper.
     */
    public function save(Salon $salon): void
    {
        $eloquentSalon = $this->salonMapper->toEloquent($salon);
        $eloquentSalon->save();
    }

    /**
     * Удалить салон по UUID.
     */
    public function delete(SalonId $id): bool
    {
        return (bool) $this->eloquentSalonModel
            ->where('uuid', $id->getValue())
            ->delete();
    }

    /**
     * Сгенерировать новый идентификатор.
     */
    public function nextIdentity(): SalonId
    {
        return SalonId::generate();
    }
}
