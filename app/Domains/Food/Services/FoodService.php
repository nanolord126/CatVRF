<?php

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\RestaurantOrder;
use App\Domains\Finances\Services\PaymentService;
use App\Domains\Finances\Services\WalletService;

class FoodService
{
    public function __construct(
        private PaymentService $paymentService,
        private WalletService $walletService
    ) {}

    public function createOrder(array $data): RestaurantOrder
    {
        $order = RestaurantOrder::create([
            'tenant_id' => $data['tenant_id'],
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'status' => 'pending',
            'correlation_id' => bin2hex(random_bytes(16)),
        ]);

        return $order;
    }

    public function payOrder(RestaurantOrder $order, string $paymentMethod = 'card'): array
    {
        if ($paymentMethod === 'wallet') {
            $this->walletService->debit($order->user, $order->amount, "Order #{$order->id}");
            $order->update(['status' => 'paid']);
            return ['success' => true];
        }

        return $this->paymentService->initializeOrderPayment($order);
    }
}
