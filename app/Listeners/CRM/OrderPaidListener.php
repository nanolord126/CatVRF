<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\OrderPaid;
use Modules\CatCRM\Domain\Entities\Deal;
use Illuminate\Support\Facades\Log;

/**
 * Order Paid Listener — Автоматическое обновление сделки при оплате заказа
 */
final class OrderPaidListener
{
    public function handle(OrderPaid $event): void
    {
        try {
            $order = $event->order;

            // Находим сделку, связанную с заказом
            $deal = Deal::where('marketplace_order_id', $order->id)
                ->where('tenant_id', $order->tenant_id)
                ->first();

            if (!$deal) {
                Log::info("CRM: No deal found for order {$order->id}");
                return;
            }

            // Перемещаем сделку на этап "Выиграно" или следующий этап
            $pipeline = $deal->pipeline;
            $wonStage = $pipeline->stages()->where('is_won_stage', true)->first();

            if ($wonStage) {
                $deal->moveToStage($wonStage, 'Заказ оплачен');
                
                // Обновляем статистику клиента
                if ($deal->customer_id) {
                    \Modules\CatCRM\Domain\Entities\Customer::find($deal->customer_id)?->updateStatistics();
                }

                Log::info("CRM: Deal {$deal->id} moved to won stage after payment");
            }

        } catch (\Throwable $e) {
            Log::error("CRM: Failed to update deal after payment", [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
