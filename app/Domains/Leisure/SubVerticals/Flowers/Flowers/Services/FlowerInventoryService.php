<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Services;

use Carbon\CarbonImmutable;

use App\Domains\Flowers\Models\FlowerProduct;
use Carbon\Carbon;
use Illuminate\Log\LogManager;

/**
 * FlowerInventoryService — управление инвентаризацией цветов.
 */
final class FlowerInventoryService
{
    public function __construct(
        private readonly LogManager $log,
    ) {}
    /**
     * Получить все цветы с истекшим сроком свежести.
     *
     * @return array<FlowerProduct>
     */
    public function getStaleFlowers(): array
    {
        return FlowerProduct::where('freshness_date', '<', CarbonImmutable::now())
            ->get()
            ->all();
    }

    /**
     * Списать цвет как просроченный.
     */
    public function expireFlower(int $flowerId, string $correlationId): void
    {
        $flower = FlowerProduct::find($flowerId);
        
        if ($flower === null) {
            $this->log->warning('Flower not found for expiration', [
                'flower_id' => $flowerId,
                'correlation_id' => $correlationId,
            ]);
            return;
        }

        $flower->update([
            'stock' => 0,
            'is_available' => false,
            'correlation_id' => $correlationId,
        ]);
    }
}
