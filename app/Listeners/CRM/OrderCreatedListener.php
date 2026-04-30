<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\OrderCreated;
use Modules\CatCRM\Application\Services\DealService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Order Created Listener — Автоматическое создание сделки в CRM при заказе
 */
final class OrderCreatedListener
{
    public function __construct(
        private readonly DealService $dealService
    ) {}

    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order;
            $tenantId = $order->tenant_id;

            // Проверяем, включена ли CRM для этого tenant
            if (!config('crm.enabled')) {
                return;
            }

            // Проверяем, существует ли уже сделка для этого заказа
            $existingDeal = \Modules\CatCRM\Domain\Entities\Deal::where('marketplace_order_id', $order->id)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($existingDeal) {
                Log::info("CRM: Deal already exists for order {$order->id}");
                return;
            }

            // Определяем вертикаль
            $vertical = $this->determineVertical($order);

            // Создаем сделку из заказа
            $deal = $this->dealService->createDealFromMarketplaceOrder([
                'order_id' => $order->id,
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'total' => $order->total_amount ?? 0,
                'first_name' => $order->customer_first_name ?? null,
                'last_name' => $order->customer_last_name ?? null,
                'email' => $order->customer_email ?? null,
                'phone' => $order->customer_phone ?? null,
                'items' => $order->items_summary ?? null,
            ], $tenantId);

            Log::info("CRM: Deal {$deal->id} created from order {$order->id}");

        } catch (\Throwable $e) {
            Log::error("CRM: Failed to create deal from order", [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function determineVertical($order): string
    {
        // Определяем вертикаль на основе данных заказа
        // Это упрощенная логика - в реальном проекте нужно более сложное определение
        
        if (isset($order->vertical) && $order->vertical) {
            return $order->vertical;
        }

        // Определяем по категориям товаров
        if (isset($order->categories)) {
            $categories = is_array($order->categories) ? $order->categories : json_decode($order->categories, true);
            
            if (in_array('hotels', $categories)) {
                return 'hotels';
            }
            if (in_array('beauty', $categories)) {
                return 'beauty';
            }
            if (in_array('flowers', $categories)) {
                return 'flowers';
            }
            if (in_array('taxi', $categories)) {
                return 'taxi';
            }
        }

        return 'default';
    }
}
