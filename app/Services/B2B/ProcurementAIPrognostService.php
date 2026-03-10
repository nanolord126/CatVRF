<?php

namespace App\Services\B2B;

use Modules\Inventory\Models\Product;
use App\Models\B2B\PurchaseOrder;
use App\Models\B2B\PurchaseOrderItem;
use App\Models\B2B\Supplier;
use Illuminate\Support\{Carbon, Str};

class ProcurementAIPrognostService
{
    /**
     * Анализ складских остатков и создание авто-заказов (Auto-Procurement)
     */
    public function analyzeAndProposeOrders()
    {
        // 1. Находим товары, чей остаток ниже страхового запаса (Safety Stock)
        // В реальном проекте 2026 года Safety Stock рассчитывается на базе нейросети Scout/Trend
        $lowStockProducts = Product::where('status', 'ACTIVE')
            ->where(function ($query) {
                // Имитация логики: текущий остаток < настроенного минимума
                $query->whereRaw('quantity <= min_safety_stock');
            })
            ->get();

        if ($lowStockProducts->isEmpty()) {
            return "Склад в норме. Авто-заказы не требуются.";
        }

        // 2. Группируем товары по поставщикам для создания заказов
        $groupedBySupplier = $lowStockProducts->groupBy('supplier_id');

        $orderCount = 0;
        foreach ($groupedBySupplier as $supplierId => $products) {
            $supplier = Supplier::find($supplierId);
            if (!$supplier) continue;

            // 3. Создаем черновик заказа (Purchase Order Draft)
            $order = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'status' => 'DRAFT',
                'correlation_id' => Str::uuid(),
                'expected_delivery_at' => Carbon::now()->addDays(3), // AI предсказывает окно доставки
            ]);

            $total = 0;
            foreach ($products as $product) {
                // Рассчитываем сколько нужно дозаказать: (MaxStock - Current) + Buffer
                $qtyToOrder = ($product->max_stock ?? 100) - $product->quantity;
                
                if ($qtyToOrder <= 0) continue;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qtyToOrder,
                    'unit_price' => $product->purchase_price ?? 0.00,
                ]);

                $total += $qtyToOrder * ($product->purchase_price ?? 0.00);
            }

            $order->update(['total_amount' => $total]);
            $orderCount++;
        }

        return "AI Процессинг завершен. Создано {$orderCount} черновиков заказов на поставку.";
    }
}
