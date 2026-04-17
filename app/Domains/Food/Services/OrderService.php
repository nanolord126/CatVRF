<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Services\FraudControlService;
use App\Services\CommissionService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

final readonly class OrderService
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly WalletService $walletService,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {}

    public function calculateCommission(int $total, bool $isB2B): int
    {
        // Food vertical: 12% for B2C, 10% for B2B
        $rate = $isB2B ? 0.10 : 0.12;
        return (int) ($total * $rate);
    }

    public function validateOrder(array $data, string $correlationId): array
    {
        $fraudScore = $this->fraudService->check(
            userId: $data['customer_id'] ?? 0,
            operationType: 'food_order_validate',
            amount: $data['total_price'] ?? 0,
            correlationId: $correlationId
        );
        
        if ($fraudScore > 75) {
            $this->logger->warning('Food order rejected due to high fraud score', [
                'fraud_score' => $fraudScore,
                'correlation_id' => $correlationId,
            ]);
            
            return ['valid' => false, 'reason' => 'high_fraud_risk', 'fraud_score' => $fraudScore];
        }

        // Check inventory for product-based vertical
        if (isset($data['items']) && is_array($data['items'])) {
            $inventoryCheck = $this->checkInventory($data['items']);
            if (!$inventoryCheck['available']) {
                $this->logger->warning('Food order rejected due to insufficient inventory', [
                    'items' => $inventoryCheck['unavailable_items'],
                    'correlation_id' => $correlationId,
                ]);
                
                return ['valid' => false, 'reason' => 'insufficient_inventory', 'unavailable_items' => $inventoryCheck['unavailable_items']];
            }
        }

        return ['valid' => true, 'fraud_score' => $fraudScore];
    }

    public function checkInventory(array $items): array
    {
        $unavailableItems = [];

        foreach ($items as $item) {
            $dishId = $item['dish_id'] ?? $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            if (!$dishId) {
                continue;
            }

            // Check dish availability
            $dish = \App\Domains\Food\Models\Dish::find($dishId);
            if (!$dish) {
                $unavailableItems[] = ['dish_id' => $dishId, 'reason' => 'not_found'];
                continue;
            }

            // Check if dish is available
            if (!$dish->is_available) {
                $unavailableItems[] = ['dish_id' => $dishId, 'reason' => 'not_available'];
                continue;
            }

            // Check if restaurant is open and offers delivery
            if ($dish->restaurant) {
                if (!$dish->restaurant->is_open) {
                    $unavailableItems[] = ['dish_id' => $dishId, 'reason' => 'restaurant_closed'];
                    continue;
                }
                if (!$dish->restaurant->is_delivery_available) {
                    $unavailableItems[] = ['dish_id' => $dishId, 'reason' => 'delivery_unavailable'];
                    continue;
                }
            }
        }

        return [
            'available' => empty($unavailableItems),
            'unavailable_items' => $unavailableItems,
        ];
    }

    public function processPayment(int $userId, int $amount, string $paymentMethod, string $correlationId): bool
    {
        // TODO: Implement payment processing with actual payment service
        // For now, return true as placeholder
        return true;
    }

    public function sendOrderConfirmation(int $userId, int $orderId, string $correlationId): void
    {
        $this->notificationService->send($userId, 'order_confirmation', [
            'order_id' => $orderId,
            'vertical' => 'food',
        ], $correlationId);
    }

    public function getDeliveryEstimate(string $address): string
    {
        // Food: product-based, physical delivery
        return '30-90 minutes (physical delivery)';
    }
}
