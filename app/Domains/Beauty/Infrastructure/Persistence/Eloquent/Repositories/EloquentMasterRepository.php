<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Beauty\Domain\Entities\Master;
use App\Domains\Beauty\Domain\Repositories\MasterRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers\MasterMapper;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyMaster as EloquentMaster;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon;
use Illuminate\Support\Collection;

/**
 * Eloquent-реализация репозитория мастеров.
 *
 * Все запросы используют глобальный scope из BeautyMaster::booted() (фильтрация по tenant_id).
 *
 * @package App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories
 */
final class EloquentMasterRepository implements MasterRepositoryInterface
{
    public function __construct(
        private EloquentMaster $eloquentMasterModel,
        private MasterMapper   $masterMapper,
    ) {}

    /**
     * Найти мастера по UUID.
     */
    public function findById(MasterId $id): ?Master
    {
        $eloquentMaster = $this->eloquentMasterModel
            ->where('uuid', $id->getValue())
            ->first();

        return $eloquentMaster ? $this->masterMapper->toDomain($eloquentMaster) : null;
    }

    /**
     * Получить всех мастеров салона.
     */
    public function findBySalonId(SalonId $salonId): Collection
    {
        $salon = BeautySalon::where('uuid', $salonId->getValue())->first();

        if (! $salon) {
            return new Collection();
        }

        return $salon->masters
            ->map(fn (EloquentMaster $m) => $this->masterMapper->toDomain($m));
    }

    /**
     * Поиск мастеров по специализации и минимальному рейтингу.
     *
     * @param array<string, mixed> $criteria  specialization, min_rating, salon_id
     */
    public function search(array $criteria): Collection
    {
        $query = $this->eloquentMasterModel->newQuery()->where('is_active', true);

        if (isset($criteria['specialization'])) {
            $spec = $criteria['specialization'];
            $query->whereJsonContains('specialization', $spec);
        }

        if (isset($criteria['min_rating'])) {
            $query->where('rating', '>=', $criteria['min_rating']);
        }

        if (isset($criteria['salon_id'])) {
            $salon = BeautySalon::where('uuid', $criteria['salon_id'])->first();
            if ($salon) {
                $query->where('salon_id', $salon->id);
            }
        }

        return $query
            ->orderByDesc('rating')
            ->get()
            ->map(fn (EloquentMaster $m) => $this->masterMapper->toDomain($m));
    }

    /**
     * Сохранить мастера.
     */
    public function save(Master $master): void
    {
        $eloquentMaster = $this->masterMapper->toEloquent($master);
        $eloquentMaster->save();
    }

    /**
     * Удалить мастера по UUID.
     */
    public function delete(MasterId $id): bool
    {
        return (bool) $this->eloquentMasterModel
            ->where('uuid', $id->getValue())
            ->delete();
    }

    /**
     * Сгенерировать новый идентификатор.
     */
    public function nextIdentity(): MasterId
    {
        return MasterId::generate();
    }
}
