<?php

declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use App\Domains\Freelance\Models\Freelancer;
use App\Domains\Freelance\Models\FreelanceOrder;
use App\Domains\Freelance\Models\FreelanceContract;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — FREELANCE SERVICE
 * Основная логика биржи фриланса: создание заказов, оплата, завершение.
 */
final readonly class FreelanceService
{
    public function __construct(
        private FraudControlService $fraudControl,
        private WalletService $walletService,
        private ContractService $contractService
    ) {}

    /**
     * Создать новый заказ на фриланс (B2C/B2B).
     */
    public function createOrder(array $data): FreelanceOrder
    {
        $correlationId = $data['correlation_id'] ?? (string) Str::uuid();

        return DB::transaction(function () use ($data, $correlationId) {
            // 1. Фрод-контроль перед сделкой
            $this->fraudControl->check([
                'user_id' => $data['client_id'],
                'operation' => 'freelance_order_create',
                'amount' => $data['budget_kopecks'],
                'correlation_id' => $correlationId
            ]);

            // 2. Создание заказа с комиссией 14% (Канон 2026)
            $order = FreelanceOrder::create([
                'tenant_id' => $data['tenant_id'] ?? tenant()->id,
                'client_id' => $data['client_id'],
                'freelancer_id' => $data['freelancer_id'],
                'offer_id' => $data['offer_id'] ?? null,
                'title' => $data['title'],
                'requirements' => $data['requirements'],
                'budget_kopecks' => $data['budget_kopecks'],
                'commission_kopecks' => (int) ($data['budget_kopecks'] * 0.14),
                'status' => 'pending',
                'deadline_at' => $data['deadline_at'] ?? now()->addDays(7),
                'is_b2b' => $data['is_b2b'] ?? false,
                'correlation_id' => $correlationId,
            ]);

            // 3. Инициализация эскроу-контракта
            $this->contractService->initEscrow($order);

            Log::channel('audit')->info('Freelance order created successfully', [
                'order_id' => $order->id,
                'client_id' => $order->client_id,
                'budget' => $order->budget_kopecks,
                'correlation_id' => $correlationId
            ]);

            return $order;
        });
    }

    /**
     * Подтвердить старт работы.
     */
    public function startOrder(int $orderId): void
    {
        $order = FreelanceOrder::findOrFail($orderId);
        
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'in_progress']);
            
            Log::channel('audit')->info('Freelance work started', [
                'order_id' => $order->id,
                'freelancer_id' => $order->freelancer_id,
                'correlation_id' => $order->correlation_id
            ]);
        });
    }

    /**
     * Завершить заказ и выплатить средства исполнителю.
     */
    public function completeOrder(int $orderId): void
    {
        $order = FreelanceOrder::with(['freelancer.user.wallet'])->findOrFail($orderId);

        DB::transaction(function () use ($order) {
            // 1. Смена статуса заказа
            $order->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // 2. Выплата фрилансеру (бюджет минус комиссия)
            $payoutAmount = $order->budget_kopecks - $order->commission_kopecks;
            $this->walletService->credit(
                walletId: $order->freelancer->user->wallet->id,
                amount: $payoutAmount,
                type: 'freelance_payout',
                correlationId: $order->correlation_id
            );

            // 3. Обновление рейтинга и статистики
            $order->freelancer->increment('completed_orders_count');

            Log::channel('audit')->info('Freelance order finalized and paid', [
                'order_id' => $order->id,
                'payout' => $payoutAmount,
                'correlation_id' => $order->correlation_id
            ]);
        });
    }
}
