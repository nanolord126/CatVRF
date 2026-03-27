<?php

declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\AutoPart;
use App\Domains\Auto\Models\Vehicle;
use App\Domains\Auto\Models\AutoRepairOrder;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: PartsService.
 * Управление складом запчастей.
 */
final readonly class PartsService
{
    /**
     * Сверхважный метод списания запчастей под заказ.
     */
    public function reservePartsForRepair(AutoRepairOrder $order, array $partsData, string $correlationId): void
    {
        DB::transaction(function () use ($order, $partsData, $correlationId) {
            foreach ($partsData as $item) {
                // $item['part_id'], $item['quantity']
                $part = AutoPart::lockForUpdate()->findOrFail($item['part_id']);

                if ($part->current_stock < $item['quantity']) {
                    throw new InsufficientStockException("Запчасти '{$part->name}' недостаточно на складе ({$part->current_stock} < {$item['quantity']})");
                }

                // Списание со склада
                $part->current_stock -= $item['quantity'];
                
                // Фиксация в заказе
                $orderParts = $order->parts_list ?? [];
                $orderParts[] = [
                    'part_id' => $part->id,
                    'sku' => $part->sku,
                    'name' => $part->name,
                    'quantity' => $item['quantity'],
                    'price' => $part->price_kopecks,
                    'total' => $part->price_kopecks * $item['quantity'],
                ];
                
                $order->parts_list = $orderParts;
                $order->parts_cost_kopecks += ($part->price_kopecks * $item['quantity']);
                
                $part->save();
                
                Log::channel('audit')->info('Part reserved for repair', [
                    'order_uuid' => $order->uuid,
                    'part_sku' => $part->sku,
                    'quantity' => $item['quantity'],
                    'correlation_id' => $correlationId,
                ]);
            }

            $order->recalculateTotal();
            $order->save();
        });
    }

    /**
     * Пополнение склада.
     */
    public function addStock(array $data, string $correlationId): AutoPart
    {
        return DB::transaction(function () use ($data, $correlationId) {
            $part = AutoPart::updateOrCreate(
                ['sku' => $data['sku'], 'tenant_id' => tenant()->id],
                [
                    'name' => $data['name'],
                    'brand' => $data['brand'] ?? 'Unknown',
                    'price_kopecks' => $data['price_kopecks'],
                    'correlation_id' => $correlationId,
                ]
            );

            $part->current_stock += ($data['quantity'] ?? 0);
            $part->save();

            Log::channel('audit')->info('Stock replenished', [
                'sku' => $part->sku,
                'added' => $data['quantity'],
                'new_total' => $part->current_stock,
                'correlation_id' => $correlationId,
            ]);

            return $part;
        });
    }

    /**
     * Проверка на низкие остатки.
     */
    public function getLowStockAlerts(): \Illuminate\Support\Collection
    {
        return AutoPart::whereRaw('current_stock <= min_stock_threshold')
            ->orderBy('current_stock', 'asc')
            ->get();
    }
}
