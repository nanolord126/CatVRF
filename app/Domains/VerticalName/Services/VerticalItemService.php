<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Services;

use App\Domains\VerticalName\DTOs\CreateVerticalItemDto;
use App\Domains\VerticalName\DTOs\SearchVerticalItemDto;
use App\Domains\VerticalName\DTOs\UpdateVerticalItemDto;
use App\Domains\VerticalName\Events\VerticalItemCreatedEvent;
use App\Domains\VerticalName\Events\VerticalItemDeletedEvent;
use App\Domains\VerticalName\Events\VerticalItemUpdatedEvent;
use App\Domains\VerticalName\Models\VerticalItem;
use App\Domains\VerticalName\Ports\VerticalItemServicePort;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * VerticalItemService — главный сервис вертикали VerticalName.
 *
 * CANON 2026 — Layer 3: Services.
 * Все мутации: FraudControlService::check() → DB::transaction() → AuditService → Event dispatch.
 * Никаких фасадов — только constructor injection.
 * correlation_id обязателен в каждом логе и событии.
 *
 * @package App\Domains\VerticalName\Services
 */
final readonly class VerticalItemService implements VerticalItemServicePort
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private Dispatcher $events,
    ) {
    }

    /**
     * Создать новый товар VerticalName.
     *
     * Поток: fraud check → transaction → create → audit → event → log.
     */
    public function createItem(CreateVerticalItemDto $dto): VerticalItem
    {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'vertical_name_create_item',
            amount: $dto->priceKopecks,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): VerticalItem {
            $item = VerticalItem::create($dto->toArray());

            $this->audit->record(
                action: 'vertical_name_item_created',
                subjectType: VerticalItem::class,
                subjectId: $item->id,
                oldValues: [],
                newValues: $item->toArray(),
                correlationId: $dto->correlationId,
            );

            $this->events->dispatch(new VerticalItemCreatedEvent(
                item: $item,
                correlationId: $dto->correlationId,
                tenantId: $dto->tenantId,
                isB2B: $dto->isB2B,
            ));

            $this->logger->info('VerticalName item created', [
                'item_id' => $item->id,
                'item_uuid' => $item->uuid,
                'tenant_id' => $dto->tenantId,
                'name' => $dto->name,
                'price_kopecks' => $dto->priceKopecks,
                'is_b2b' => $dto->isB2B,
                'correlation_id' => $dto->correlationId,
            ]);

            return $item;
        });
    }

    /**
     * Обновить товар VerticalName.
     *
     * Поток: fraud check → transaction → update → audit → event → log.
     */
    public function updateItem(UpdateVerticalItemDto $dto): VerticalItem
    {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'vertical_name_update_item',
            amount: $dto->priceKopecks ?? 0,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): VerticalItem {
            $item = VerticalItem::where('tenant_id', $dto->tenantId)
                ->findOrFail($dto->itemId);

            $oldValues = $item->toArray();

            $item->update($dto->toArray());
            $item->refresh();

            $this->audit->record(
                action: 'vertical_name_item_updated',
                subjectType: VerticalItem::class,
                subjectId: $item->id,
                oldValues: $oldValues,
                newValues: $item->toArray(),
                correlationId: $dto->correlationId,
            );

            $this->events->dispatch(new VerticalItemUpdatedEvent(
                item: $item,
                correlationId: $dto->correlationId,
                tenantId: $dto->tenantId,
                changedFields: array_keys($dto->toArray()),
            ));

            $this->logger->info('VerticalName item updated', [
                'item_id' => $item->id,
                'tenant_id' => $dto->tenantId,
                'changed_fields' => array_keys($dto->toArray()),
                'correlation_id' => $dto->correlationId,
            ]);

            return $item;
        });
    }

    /**
     * Мягкое удаление товара.
     */
    public function deleteItem(int $itemId, int $tenantId, string $correlationId): bool
    {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'vertical_name_delete_item',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($itemId, $tenantId, $correlationId): bool {
            $item = VerticalItem::where('tenant_id', $tenantId)
                ->findOrFail($itemId);

            $oldValues = $item->toArray();
            $item->delete();

            $this->audit->record(
                action: 'vertical_name_item_deleted',
                subjectType: VerticalItem::class,
                subjectId: $itemId,
                oldValues: $oldValues,
                newValues: [],
                correlationId: $correlationId,
            );

            $this->events->dispatch(new VerticalItemDeletedEvent(
                itemId: $itemId,
                correlationId: $correlationId,
                tenantId: $tenantId,
            ));

            $this->logger->info('VerticalName item deleted', [
                'item_id' => $itemId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    /**
     * Получить товар по ID (tenant-scoped).
     */
    public function getById(int $itemId, int $tenantId): VerticalItem
    {
        return VerticalItem::where('tenant_id', $tenantId)
            ->findOrFail($itemId);
    }

    /**
     * Поиск товаров с фильтрацией и пагинацией.
     */
    public function search(SearchVerticalItemDto $dto): LengthAwarePaginator
    {
        $query = VerticalItem::where('tenant_id', $dto->tenantId);

        if ($dto->query !== null) {
            $query->where(function ($q) use ($dto): void {
                $q->where('name', 'ilike', '%' . $dto->query . '%')
                    ->orWhere('description', 'ilike', '%' . $dto->query . '%')
                    ->orWhere('sku', 'ilike', '%' . $dto->query . '%');
            });
        }

        if ($dto->category !== null) {
            $query->where('category', $dto->category);
        }

        if ($dto->priceMin !== null) {
            $query->where('price_kopecks', '>=', $dto->priceMin);
        }

        if ($dto->priceMax !== null) {
            $query->where('price_kopecks', '<=', $dto->priceMax);
        }

        if ($dto->ratingMin !== null) {
            $query->where('rating', '>=', $dto->ratingMin);
        }

        if ($dto->inStockOnly === true) {
            $query->where('stock_quantity', '>', 0);
        }

        if ($dto->b2bOnly === true) {
            $query->where('is_b2b_available', true);
        }

        $sortBy = in_array($dto->sortBy, ['name', 'price_kopecks', 'rating', 'created_at'], true)
            ? $dto->sortBy
            : 'created_at';

        $sortDir = $dto->sortDirection === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)
            ->paginate(perPage: $dto->perPage, page: $dto->page);
    }

    /**
     * B2B-каталог (только для Tenant Panel / B2B API).
     */
    public function getB2bCatalog(int $tenantId, int $perPage = 20): LengthAwarePaginator
    {
        return VerticalItem::where('tenant_id', $tenantId)
            ->b2bAvailable()
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * B2C-каталог (публичная витрина).
     */
    public function getPublicCatalog(int $tenantId, int $perPage = 20): LengthAwarePaginator
    {
        return VerticalItem::where('tenant_id', $tenantId)
            ->published()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Зарезервировать товар (для корзины, 20 мин).
     *
     * CANON 2026: lockForUpdate + проверка available.
     */
    public function reserveStock(int $itemId, int $tenantId, int $quantity, string $correlationId): bool
    {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'vertical_name_reserve_stock',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($itemId, $tenantId, $quantity, $correlationId): bool {
            $item = VerticalItem::where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($itemId);

            if ($item->stock_quantity < $quantity) {
                $this->logger->warning('VerticalName insufficient stock for reservation', [
                    'item_id' => $itemId,
                    'requested' => $quantity,
                    'available' => $item->stock_quantity,
                    'correlation_id' => $correlationId,
                ]);

                return false;
            }

            $item->decrement('stock_quantity', $quantity);

            $this->audit->record(
                action: 'vertical_name_stock_reserved',
                subjectType: VerticalItem::class,
                subjectId: $itemId,
                oldValues: ['stock_quantity' => $item->stock_quantity + $quantity],
                newValues: ['stock_quantity' => $item->stock_quantity],
                correlationId: $correlationId,
            );

            $this->logger->info('VerticalName stock reserved', [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'remaining' => $item->stock_quantity,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    /**
     * Снять резерв (при истечении корзины или отмене).
     */
    public function releaseStock(int $itemId, int $tenantId, int $quantity, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($itemId, $tenantId, $quantity, $correlationId): bool {
            $item = VerticalItem::where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($itemId);

            $item->increment('stock_quantity', $quantity);

            $this->audit->record(
                action: 'vertical_name_stock_released',
                subjectType: VerticalItem::class,
                subjectId: $itemId,
                oldValues: ['stock_quantity' => $item->stock_quantity - $quantity],
                newValues: ['stock_quantity' => $item->stock_quantity],
                correlationId: $correlationId,
            );

            $this->logger->info('VerticalName stock released', [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'new_stock' => $item->stock_quantity,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }
}
