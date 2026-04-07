<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\BeautyMaster;
use App\Domains\Beauty\Models\PortfolioItem;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * PortfolioService — управление портфолио работ мастеров.
 *
 * Добавление, получение и удаление работ из портфолио мастера
 * с fraud-проверкой и audit-логированием.
 */
final readonly class PortfolioService
{
    public function __construct(
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Добавить работу в портфолио.
     */
    public function addItem(BeautyMaster $master, array $data, ?string $correlationId = null): PortfolioItem
    {
        $correlationId ??= Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'portfolio_add',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($master, $data, $correlationId): PortfolioItem {
            $item = PortfolioItem::create(array_merge($data, [
                'master_id' => $master->id,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'tenant_id' => $master->tenant_id,
            ]));

            $this->logger->info('Portfolio item added', [
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
    public function deleteItem(PortfolioItem $item, ?string $correlationId = null): bool
    {
        $correlationId ??= Str::uuid()->toString();

        return $this->db->transaction(function () use ($item, $correlationId): bool {
            $result = (bool) $item->delete();

            $this->logger->info('Portfolio item deleted', [
                'item_id' => $item->id,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        });
    }
}
