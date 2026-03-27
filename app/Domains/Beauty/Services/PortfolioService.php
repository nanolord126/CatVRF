<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\PortfolioItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Portfolio Service (Layer 3)
 * Управление работами мастеров (До/После).
 */
final readonly class PortfolioService
{
    /**
     * Добавить работу в портфолио.
     */
    public function addItem(Master $master, array $data, string $correlationId = null): PortfolioItem
    {
        $correlationId ??= (string) Str::uuid();

        return DB::transaction(function () use ($master, $data, $correlationId) {
            $item = PortfolioItem::create(array_merge($data, [
                'master_id' => $master->id,
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'tenant_id' => $master->tenant_id,
            ]));

            Log::channel('audit')->info('Portfolio item added', [
                'master_id' => $master->id,
                'item_id' => $item->id,
                'correlation_id' => $correlationId,
            ]);

            return $item;
        });
    }

    /**
     * Получить работы мастера.
     */
    public function getMasterPortfolio(int $masterId): Collection
    {
        return PortfolioItem::where('master_id', $masterId)->get();
    }

    /**
     * Удалить работу.
     */
    public function deleteItem(PortfolioItem $item, string $correlationId = null): bool
    {
        $correlationId ??= (string) Str::uuid();

        return DB::transaction(function () use ($item, $correlationId) {
            $result = $item->delete();

            Log::channel('audit')->info('Portfolio item deleted', [
                'item_id' => $item->id,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        });
    }
}
