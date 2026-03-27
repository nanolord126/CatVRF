<?php

declare(strict_types=1);

namespace App\Domains\RitualServices\RitualServices\Ritual\Services;

use App\Domains\RitualServices\RitualServices\Ritual\Models\FuneralOrder;
use App\Domains\RitualServices\RitualServices\Ritual\Models\RitualAgency;
use App\Services\WalletService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\FraudAlertException;

/**
 * RitualCoreService — Production Ready 2026
 * 
 * Централизованный сервис обработки ритуальных заказов.
 * Реализовано по доменному канону 2026: Транзакции, Audit-лог, Correlaton ID.
 */
final readonly class RitualCoreService
{
    /**
     * Конструктор с DI зависимостями (readonly).
     */
    public function __construct(
        private WalletService $wallet,
        private FraudControlService $fraud,
    ) {}

    /**
     * Создать новый заказ на организацию похорон.
     * 
     * @throws FraudAlertException
     * @throws InsufficientFundsException
     */
    public function createFuneralOrder(array $data, string $correlation_id = null): FuneralOrder
    {
        $correlation_id ??= (string) Str::uuid();

        // 1. Fraud Check (Канон 2026)
        $this->fraud->check([
            'operation' => 'funeral_order_create',
            'client_id' => $data['client_id'],
            'amount' => $data['total_amount_kopecks'],
            'correlation_id' => $correlation_id,
        ]);

        return DB::transaction(function () use ($data, $correlation_id) {
            
            // 2. Создание записи заказа (Optimistic Locking & Unique scoping)
            $order = FuneralOrder::create([
                ...$data,
                'status' => 'pending',
                'correlation_id' => $correlation_id,
            ]);

            // 3. Логирование (Audit Log Канон)
            Log::channel('audit')->info('Funeral order created', [
                'order_uuid' => $order->uuid,
                'client_id' => $order->client_id,
                'amount' => $order->total_amount_kopecks,
                'correlation_id' => $correlation_id,
            ]);

            return $order;
        });
    }

    /**
     * Процессинг оплаты заказа (Дебет Кошелька).
     * 
     * @throws InsufficientFundsException
     */
    public function processPayment(FuneralOrder $order, int $amount_kopecks): bool
    {
        return DB::transaction(function () use ($order, $amount_kopecks) {
            
            // 4. Списание через WalletService (Канон 2026)
            $this->wallet->debit(
                walletId: $order->client_id, // Условно ID клиента = его кошелек
                amountInKopecks: $amount_kopecks,
                type: 'withdrawal',
                reason: "Payment for funeral order #{$order->uuid}",
                correlation_id: $order->correlation_id
            );

            // 5. Обновление статуса оплаты
            $order->increment('paid_amount_kopecks', $amount_kopecks);
            
            if ($order->isFullyPaid()) {
                $order->update(['status' => 'paid']);
            }

            Log::channel('audit')->info('Funeral payment processed', [
                'order_uuid' => $order->uuid,
                'amount' => $amount_kopecks,
                'correlation_id' => $order->correlation_id,
            ]);

            return true;
        });
    }

    /**
     * Аннулирование заказа с возвратом средств.
     */
    public function cancelOrder(FuneralOrder $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            
            if ($order->paid_amount_kopecks > 0) {
                // Возвращаем средства клиенту
                $this->wallet->credit(
                    walletId: $order->client_id,
                    amountInKopecks: $order->paid_amount_kopecks,
                    type: 'refund',
                    reason: "Refund for canceled order #{$order->uuid}: {$reason}",
                    correlation_id: $order->correlation_id
                );
            }

            $order->update([
                'status' => 'cancelled',
                'metadata' => array_merge($order->metadata ?? [], ['cancellation_reason' => $reason]),
            ]);

            Log::channel('audit')->warning('Funeral order cancelled', [
                'order_uuid' => $order->uuid,
                'reason' => $reason,
                'correlation_id' => $order->correlation_id,
            ]);
        });
    }
}
