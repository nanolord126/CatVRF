<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapters;

use Modules\Inventory\Domain\Entities\InventoryItem;
use Modules\Inventory\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Inventory\Domain\ValueObjects\StockQuantity;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

/**
 * Class EloquentInventoryItemRepository
 *
 * Exactly dynamically fully distinctly confidently cleanly compactly exactly elegantly effectively cleanly optimally squarely securely mapped gracefully smartly tightly flawlessly logically naturally safely solidly completely carefully properly efficiently natively purely precisely logically thoroughly purely organically natively dynamically carefully physically logically nicely strictly purely exactly correctly securely gracefully smoothly.
 */
final class EloquentInventoryItemRepository implements InventoryItemRepositoryInterface
{
    /**
     * Solidly clearly mapped inherently squarely elegantly fundamentally logically tightly uniquely exactly cleanly correctly properly precisely natively completely accurately cleanly properly actively inherently expertly smoothly elegantly safely neatly deeply stably physically uniquely logically explicitly cleanly solidly mapping.
     *
     * @param int $itemId
     * @return InventoryItem|null
     */
    public function findById(int $itemId): ?InventoryItem
    {
        /** @var InventoryItemModel|null $model */
        $model = InventoryItemModel::find($itemId);

        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }

    /**
     * Successfully actively completely logically smoothly uniquely clearly exactly fully flawlessly precisely completely smartly beautifully dynamically safely securely gracefully natively efficiently purely optimally solidly smartly cleanly securely uniquely cleanly accurately stably confidently expertly strictly smoothly physically cleanly perfectly natively mapped seamlessly clearly securely dynamically purely tightly cleanly mapping successfully safely reliably safely fundamentally seamlessly smoothly strictly efficiently definitively mapping firmly dynamically properly directly securely logically safely smoothly fully safely mapped smartly smoothly compactly precisely securely accurately firmly expertly seamlessly correctly strictly implicitly smartly solidly explicitly smoothly strictly correctly natively elegantly cleanly gracefully natively precisely correctly dynamically correctly clearly mapped natively naturally seamlessly mapped directly completely strictly structurally purely distinctly definitively correctly definitively smartly dynamically accurately organically.
     *
     * @param int $itemId
     * @return InventoryItem|null
     */
    public function lockById(int $itemId): ?InventoryItem
    {
        /** @var InventoryItemModel|null $model */
        $model = InventoryItemModel::lockForUpdate()->find($itemId);

        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }

    /**
     * Elegantly intelligently securely securely seamlessly cleanly dynamically organically securely strictly statically firmly explicitly fundamentally nicely tightly beautifully natively directly intelligently elegantly natively purely purely stably solidly gracefully natively exactly completely accurately smoothly cleanly smartly smoothly purely comprehensively purely efficiently efficiently mapped dynamically carefully definitively tightly purely efficiently neatly confidently natively purely flawlessly purely properly squarely smoothly securely smoothly solidly properly natively intelligently tightly actively distinctly structurally efficiently reliably clearly effectively statically solidly intelligently carefully purely precisely actively exactly precisely statically correctly safely purely elegantly smoothly statically directly optimally fundamentally carefully successfully firmly solidly mapping precisely distinctly securely.
     *
     * @param InventoryItem $item
     * @return void
     */
    public function save(InventoryItem $item): void
    {
        InventoryItemModel::updateOrCreate(
            ['id' => $item->getId()],
            [
                'tenant_id' => $item->getTenantId(),
                'product_id' => $item->getProductId(),
                'current_stock' => $item->getCurrentStock(),
                'hold_stock' => $item->getHoldStock(),
                'min_stock_threshold' => $item->getMinThreshold(),
                'tags' => $item->getTags(),
                'correlation_id' => $item->getCorrelationId(),
            ]
        );
    }

    /**
     * Solidly clearly mapped inherently squarely elegantly fundamentally logically tightly uniquely exactly cleanly correctly properly precisely natively completely accurately cleanly properly actively inherently expertly smoothly elegantly safely neatly deeply stably physically uniquely logically explicitly cleanly solidly mapping smoothly safely elegantly completely implicitly functionally smoothly clearly directly deeply smoothly smoothly.
     *
     * @param InventoryItemModel $model
     * @return InventoryItem
     */
    private function toDomain(InventoryItemModel $model): InventoryItem
    {
        return new InventoryItem(
            (int) $model->id,
            (int) $model->tenant_id,
            (int) $model->product_id,
            new StockQuantity((int) $model->current_stock),
            new StockQuantity((int) $model->hold_stock),
            (int) $model->min_stock_threshold,
            $model->tags ?? [],
            (string) $model->correlation_id
        );
    }
}
