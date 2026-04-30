<?php

declare(strict_types=1);

namespace App\Domains\Shared\Loyalty\Services;

use App\Domains\Shared\Loyalty\Models\CashbackRule;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Services\WalletService;

final readonly class CashbackService
{
    use WithAuditLogging;

    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * Calculate and accrue cashback for an order.
     *
     * @param  SupermarketOrder  $order
     * @return void
     */
    public function calculateAndAccrue(SupermarketOrder $order): void
    {
        $correlationId = $this->generateCorrelationId();

        DB::transaction(function () use ($order, $correlationId) {
            // Find the seller's cashback rule
            $rule = CashbackRule::where('tenant_id', $order->tenant_id)
                ->where('vertical', 'supermarket')
                ->where(function ($query) use ($order) {
                    $query->whereNull('sub_vertical')
                        ->orWhere('sub_vertical', $order->sub_vertical);
                })
                ->where('is_active', true)
                ->where('min_order_amount', '<=', $order->total_amount)
                ->first();

            if (!$rule) {
                Log::info('No active cashback rule found for order', [
                    'order_id' => $order->id,
                    'tenant_id' => $order->tenant_id,
                    'sub_vertical' => $order->sub_vertical,
                    'total_amount' => $order->total_amount,
                    'correlation_id' => $correlationId,
                ]);
                return;
            }

            // Calculate cashback amount
            $cashbackAmount = (int) ($order->total_amount * ($rule->percent / 100));

            if ($cashbackAmount <= 0) {
                Log::info('Cashback amount is zero or negative', [
                    'order_id' => $order->id,
                    'total_amount' => $order->total_amount,
                    'percent' => $rule->percent,
                    'cashback_amount' => $cashbackAmount,
                    'correlation_id' => $correlationId,
                ]);
                return;
            }

            // Get tenant for shop name
            $tenant = $order->tenant;
            $shopName = $tenant?->name ?? 'Unknown Shop';

            // Accrue bonus to buyer's wallet
            $this->walletService->deposit(
                userId: $order->user_id,
                tenantId: $order->tenant_id,
                amountCents: $cashbackAmount,
                currency: 'RUB',
                metadata: [
                    'order_id' => $order->id,
                    'vertical' => 'supermarket',
                    'sub_vertical' => $order->sub_vertical,
                    'cashback_percent' => $rule->percent,
                    'cashback_rule_id' => $rule->id,
                ],
                description: "Cashback from {$shopName}",
                correlationId: $correlationId,
            );

            // Log the cashback accrual
            $this->logAction(
                action: 'cashback_accrued',
                entityType: 'supermarket_order',
                entityId: $order->id,
                userId: $order->user_id,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $order->tenant_id,
                    'shop_name' => $shopName,
                    'total_amount' => $order->total_amount,
                    'cashback_percent' => $rule->percent,
                    'cashback_amount' => $cashbackAmount,
                    'rule_id' => $rule->id,
                ],
            );

            Log::info('Cashback accrued successfully', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'tenant_id' => $order->tenant_id,
                'cashback_amount' => $cashbackAmount,
                'percent' => $rule->percent,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
