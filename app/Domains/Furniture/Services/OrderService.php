<?php declare(strict_types=1);

namespace App\Domains\Furniture\Services;

use App\Services\FraudControlService;
use App\Services\Payment\WalletService;
use App\Services\CommissionService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

final readonly class OrderService
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly WalletService $walletService,
        private readonly CommissionService $commissionService,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {}

    public function calculateCommission(int $total, bool $isB2B): int
    {
        // Furniture vertical: 14% for B2C, 11% for B2B
        $rate = $isB2B ? 0.11 : 0.14;
        return (int) ($total * $rate);
    }

    public function validateOrder(array $data, string $correlationId): array
    {
        $fraudScore = $this->fraudService->check($data, $correlationId);
        
        if ($fraudScore > 80) {
            $this->logger->warning('Furniture order rejected due to high fraud score', [
                'fraud_score' => $fraudScore,
                'correlation_id' => $correlationId,
            ]);
            
            return ['valid' => false, 'reason' => 'high_fraud_risk', 'fraud_score' => $fraudScore];
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $inventoryCheck = $this->checkInventory($data['items']);
            if (!$inventoryCheck['available']) {
                $this->logger->warning('Furniture order rejected due to insufficient inventory', [
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
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;
            
            if (!$productId) {
                continue;
            }
            
            // TODO: Implement actual inventory check for furniture (may have lead time)
        }
        
        return [
            'available' => empty($unavailableItems),
            'unavailable_items' => $unavailableItems,
        ];
    }

    public function processPayment(int $userId, int $amount, string $paymentMethod, string $correlationId): bool
    {
        return $this->walletService->deduct($userId, $amount, $paymentMethod, $correlationId);
    }

    public function sendOrderConfirmation(int $userId, int $orderId, string $correlationId): void
    {
        $this->notificationService->send($userId, 'order_confirmation', [
            'order_id' => $orderId,
            'vertical' => 'furniture',
        ], $correlationId);
    }

    public function getDeliveryEstimate(string $address): string
    {
        // Furniture: product-based, physical delivery
        return '5-14 business days (physical delivery)';
    }
}
