<?php

declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use App\Domains\Freelance\Models\FreelanceOrder;
use App\Domains\Freelance\Models\FreelanceContract;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — CONTRACT SERVICE
 * Управление эскроу-счетами (Безопасная сделка) и разрешением споров.
 */
final readonly class ContractService
{
    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * Регистрация эскроу-факта для нового заказа.
     */
    public function initEscrow(FreelanceOrder $order): FreelanceContract
    {
        return FreelanceContract::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'escrow_amount_kopecks' => $order->budget_kopecks,
            'escrow_status' => 'awaiting',
            'correlation_id' => $order->correlation_id
        ]);
    }

    /**
     * Холдирование средств на кошельке клиента (Безопасная сделка).
     */
    public function holdFunds(int $contractId): void
    {
        $contract = FreelanceContract::with(['order.client.wallet'])->findOrFail($contractId);

        DB::transaction(function () use ($contract) {
            // Блокировка суммы на кошельке клиента до завершения работ
            $this->walletService->hold(
                walletId: $contract->order->client->wallet->id,
                amount: $contract->escrow_amount_kopecks,
                correlationId: $contract->correlation_id
            );

            $contract->update(['escrow_status' => 'held']);
            $contract->order->update(['status' => 'escrow_hold']);

            Log::channel('audit')->info('Freelance budget held on client wallet', [
                'contract_number' => $contract->contract_number,
                'amount' => $contract->escrow_amount_kopecks,
                'correlation_id' => $contract->correlation_id
            ]);
        });
    }

    /**
     * Арбитражное решение при споре по заказу.
     * $refundPercent — сколько % вернуть клиенту (0-100).
     */
    public function resolveDispute(int $contractId, string $resolution, float $refundPercent = 50): void
    {
        $contract = FreelanceContract::with(['order.client.wallet', 'order.freelancer.user.wallet'])->findOrFail($contractId);

        DB::transaction(function () use ($contract, $resolution, $refundPercent) {
            $refundAmount = (int) ($contract->escrow_amount_kopecks * ($refundPercent / 100));
            $payoutAmount = $contract->escrow_amount_kopecks - $refundAmount;

            // 1. Возврат части средств клиенту
            if ($refundAmount > 0) {
                $this->walletService->release_hold(
                    walletId: $contract->order->client->wallet->id,
                    amount: $refundAmount,
                    correlationId: $contract->correlation_id
                );
            }

            // 2. Выплата остатка фрилансеру
            if ($payoutAmount > 0) {
                $this->walletService->credit(
                    walletId: $contract->order->freelancer->user->wallet->id,
                    amount: $payoutAmount,
                    type: 'arbitration_resolved_payout',
                    correlationId: $contract->correlation_id
                );
            }

            $contract->update([
                'escrow_status' => $refundPercent >= 100 ? 'refunded' : 'released',
                'arbitration_comment' => $resolution
            ]);

            $contract->order->update(['status' => 'completed']);

            Log::channel('audit')->warning('Freelance dispute resolved by court', [
                'contract_id' => $contract->id,
                'payout' => $payoutAmount,
                'refund' => $refundAmount,
                'reason' => $resolution,
                'correlation_id' => $contract->correlation_id
            ]);
        });
    }
}
