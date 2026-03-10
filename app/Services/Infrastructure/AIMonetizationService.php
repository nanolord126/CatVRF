<?php

namespace App\Services\Infrastructure;

use App\Models\Order;
use App\Models\Tenant;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AIMonetizationService
{
    /**
     * Списывает 5% комиссию за использование AI-конструктора с кошелька тенанта.
     * Transaction: Order -> Tenant Business Wallet -> Platform Fee.
     */
    public function collectAiFee(Order $order, string $type): ?Transaction
    {
        return DB::transaction(function () use ($order, $type) {
            $feePercentage = 0.05; // Фиксированная комиссия 5%
            $aiFeeAmount = $order->total * $feePercentage;
            $correlationId = (string) Str::uuid();

            /** @var Tenant $tenant */
            $tenant = $order->tenant;

            // Записываем комиссию в метаданные заказа
            $order->update([
                "ai_constructor_type" => $type,
                "ai_commission_amount" => $aiFeeAmount,
                "correlation_id" => $correlationId,
            ]);

            // Проводим транзакцию через laravel-wallet
            // В данной архитектуре "Platform" является получателем комиссии
            return $tenant->withdraw($aiFeeAmount, [
                "type" => "ai_fee",
                "ai_type" => $type,
                "order_id" => $order->id,
                "correlation_id" => $correlationId,
                "description" => "AI Construction Fee (5%) for Order #{$order->id}",
            ]);
        });
    }
}

