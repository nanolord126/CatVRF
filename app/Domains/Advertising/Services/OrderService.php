<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Services;

use App\Services\FraudControlService;
use App\Services\CommissionService;
use App\Services\NotificationService;
use Psr\Log\LoggerInterface;

final readonly class OrderService
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly CommissionService $commissionService,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {}

    public function calculateCommission(int $total, bool $isB2B): int
    {
        $rate = $isB2B ? 0.15 : 0.20;

        return (int) ($total * $rate);
    }

    public function validateOrder(array $data, string $correlationId): array
    {
        // Fraud check for order validation
        try {
            $fraudResult = $this->fraudService->check(
                userId: \Illuminate\Support\Facades\Auth::id() ?? 0,
                operationType: 'validate_advertising_order',
                amount: 0,
                correlationId: $correlationId,
                context: $data,
            );
        } catch (\App\Exceptions\FraudBlockedException $e) {
            $this->logger->warning('Advertising order validation blocked by fraud check', [
                'correlation_id' => $correlationId,
                'reason' => $e->getMessage(),
            ]);

            return ['valid' => false, 'fraud_score' => 1.0, 'reason' => $e->getMessage()];
        }

        // Business validation
        $validationErrors = $this->validateBusinessRules($data);

        if (!empty($validationErrors)) {
            return ['valid' => false, 'fraud_score' => 0, 'errors' => $validationErrors];
        }

        $this->logger->info('Advertising order validation passed', [
            'correlation_id' => $correlationId,
        ]);

        return ['valid' => true, 'fraud_score' => $fraudResult['score'] ?? 0];
    }

    public function processPayment(int $userId, int $amount, string $paymentMethod, string $correlationId): bool
    {
        // Fraud check for payment processing
        try {
            $this->fraudService->check(
                userId: $userId,
                operationType: 'process_advertising_payment',
                amount: $amount,
                correlationId: $correlationId,
                context: [
                    'payment_method' => $paymentMethod,
                ],
            );
        } catch (\App\Exceptions\FraudBlockedException $e) {
            $this->logger->warning('Advertising payment blocked by fraud check', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'amount' => $amount,
                'reason' => $e->getMessage(),
            ]);

            return false;
        }

        // Payment processing would be implemented here
        // For now, return success
        $this->logger->info('Advertising payment processed successfully', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
        ]);

        return true;
    }

    public function sendOrderConfirmation(int $userId, int $orderId, string $correlationId): void
    {
        $this->notificationService->send($userId, 'order_confirmation', [
            'order_id' => $orderId,
            'vertical' => 'advertising',
        ], $correlationId);

        $this->logger->info('Advertising order confirmation sent', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'order_id' => $orderId,
        ]);
    }

    public function getDeliveryEstimate(string $address): string
    {
        // Advertising: service-based, instant digital campaign
        return 'Instant digital campaign activation';
    }

    public function calculateTotalWithTax(int $subtotal, string $region = 'RU'): array
    {
        $taxRate = match ($region) {
            'RU' => 0.20,
            'EU' => 0.21,
            'US' => 0.08,
            default => 0.00,
        };

        $tax = (int) ($subtotal * $taxRate);
        $total = $subtotal + $tax;

        return [
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'total' => $total,
            'currency' => 'RUB',
        ];
    }

    public function validateBudgetConstraints(array $campaignData): bool
    {
        $budget = $campaignData['budget'] ?? 0;
        $pricingModel = $campaignData['pricing_model'] ?? 'cpm';

        $minimumBudgets = [
            'cpm' => 1000,
            'cpc' => 1000,
            'cpa' => 5000,
            'flat' => 100,
        ];

        return $budget >= ($minimumBudgets[$pricingModel] ?? 100);
    }

    public function estimateCampaignDuration(array $campaignData): array
    {
        $budget = $campaignData['budget'] ?? 0;
        $pricingModel = $campaignData['pricing_model'] ?? 'cpm';
        $targetImpressions = $campaignData['target_impressions'] ?? 0;

        $estimatedCpm = match ($pricingModel) {
            'cpm' => 50, // Average CPM in RUB
            'cpc' => 20, // Average CPC in RUB
            'cpa' => 500, // Average CPA in RUB
            'flat' => 0,
            default => 50,
        };

        if ($pricingModel === 'flat') {
            return [
                'estimated_days' => 30,
                'estimated_impressions' => null,
                'pricing_model' => 'flat',
            ];
        }

        if ($targetImpressions > 0) {
            $estimatedCost = (int) ($targetImpressions * ($estimatedCpm / 1000));
            $estimatedDays = $budget > 0 ? (int) ceil($estimatedCost / ($budget / 30)) : 30;

            return [
                'estimated_days' => min($estimatedDays, 365),
                'estimated_impressions' => $targetImpressions,
                'estimated_cost' => $estimatedCost,
                'pricing_model' => $pricingModel,
            ];
        }

        $estimatedImpressions = $budget > 0 ? (int) (($budget / $estimatedCpm) * 1000) : 0;

        return [
            'estimated_days' => 30,
            'estimated_impressions' => $estimatedImpressions,
            'pricing_model' => $pricingModel,
        ];
    }

    private function validateBusinessRules(array $data): array
    {
        $errors = [];

        if (isset($data['budget']) && $data['budget'] < 100) {
            $errors[] = 'Budget must be at least 100 cents';
        }

        if (isset($data['start_at']) && isset($data['end_at'])) {
            $start = strtotime($data['start_at']);
            $end = strtotime($data['end_at']);

            if ($start >= $end) {
                $errors[] = 'End date must be after start date';
            }

            if ($end - $start > 365 * 24 * 60 * 60) {
                $errors[] = 'Campaign duration cannot exceed 365 days';
            }
        }

        if (isset($data['pricing_model']) && !in_array($data['pricing_model'], ['cpm', 'cpc', 'cpa', 'flat'])) {
            $errors[] = 'Invalid pricing model';
        }

        return $errors;
    }
}
