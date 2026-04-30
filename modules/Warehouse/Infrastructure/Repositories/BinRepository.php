<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Warehouse\Domain\Entities\Bin;
use Modules\Warehouse\Domain\Repositories\BinRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\BinId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Infrastructure\Models\BinModel;
use Illuminate\Database\DatabaseManager;

final class BinRepository implements BinRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db
    ) {}

    public function save(Bin $bin): void
    {
        BinModel::updateOrCreate(
            ['id' => $bin->getId()->toString()],
            $bin->toArray()
        );
    }

    public function findById(BinId $id): ?Bin
    {
        $model = BinModel::with(['zone', 'inventoryItems'])->find($id->toString());
        return $model?->toDomain();
    }

    public function findByZoneId(ZoneId $zoneId): array
    {
        return BinModel::where('zone_id', $zoneId->toString())
            ->orderBy('code')
            ->get()
            ->map(fn (BinModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByCode(string $code): ?Bin
    {
        $model = BinModel::with(['zone', 'inventoryItems'])->where('code', $code)->first();
        return $model?->toDomain();
    }

    public function findActiveByZoneId(ZoneId $zoneId): array
    {
        return BinModel::where('zone_id', $zoneId->toString())
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (BinModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByType(string $binType): array
    {
        return BinModel::where('bin_type', $binType)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (BinModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findAvailableBins(ZoneId $zoneId, int $minCapacity = 0): array
    {
        return BinModel::where('zone_id', $zoneId->toString())
            ->where('is_active', true)
            ->where('capacity', '>=', $minCapacity)
            ->whereRaw('(current_stock < capacity)')
            ->orderBy('code')
            ->get()
            ->map(fn (BinModel $model) => $model->toDomain())
            ->toArray();
    }

    public function delete(BinId $id): void
    {
        $this->db->transaction(function () use ($id) {
            $bin = BinModel::find($id->toString());
            
            if (!$bin) {
                return;
            }

            // Проверяем, что ячейка пуста перед удалением
            if ($bin->current_stock > 0) {
                throw new \RuntimeException('Cannot delete bin with stock');
            }

            BinModel::destroy($id->toString());
        });
    }

    public function exists(BinId $id): bool
    {
        return BinModel::where('id', $id->toString())->exists();
    }

    public function existsByCode(string $code): bool
    {
        return BinModel::where('code', $code)->exists();
    }

    public function countByZoneId(ZoneId $zoneId): int
    {
        return BinModel::where('zone_id', $zoneId->toString())->count();
    }

    public function countActiveByZoneId(ZoneId $zoneId): int
    {
        return BinModel::where('zone_id', $zoneId->toString())
            ->where('is_active', true)
            ->count();
    }
}
