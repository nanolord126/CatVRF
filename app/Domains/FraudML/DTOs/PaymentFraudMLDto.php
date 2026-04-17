<?php

declare(strict_types=1);

namespace App\Domains\FraudML\DTOs;

/**
 * PaymentFraudMLDto - Payment-specific fraud detection DTO
 * 
 * Extends base OperationDto with payment-specific fields to reduce false positives
 * in Medical and other payment-critical verticals.
 * 
 * CANON 2026 - Production Ready
 * 
 * @package App\Domains\FraudML\DTOs
 */
final readonly class PaymentFraudMLDto
{
    public function __construct(
        public int $tenant_id,
        public int $user_id,
        public string $operation_type,
        public int $amount_kopecks,
        public string $ip_address,
        public string $device_fingerprint,
        public string $correlation_id,
        public string $idempotency_key,
        
        // Payment-specific fields
        public ?string $vertical_code = null,
        public ?float $current_quota_usage_ratio = null,
        public ?int $wallet_balance_kopecks = null,
        public ?string $urgency_level = null, // 'low', 'medium', 'high', 'emergency'
        public ?float $previous_payment_success_rate_7d = null,
        public ?int $payment_count_24h = null,
        public ?int $payment_sum_24h_kopecks = null,
        public ?bool $is_emergency_payment = null,
        public ?float $consultation_price_spike_ratio = null,
        public ?int $previous_failures_24h = null,
        
        // Additional context
        public array $additional_context = []
    ) {}

    /**
     * Convert amount from kopecks to rubles for ML features
     */
    public function amountRubles(): float
    {
        return $this->amount_kopecks / 100.0;
    }

    /**
     * Convert wallet balance from kopecks to rubles for ML features
     */
    public function walletBalanceRubles(): float
    {
        return $this->wallet_balance_kopecks ? $this->wallet_balance_kopecks / 100.0 : 0.0;
    }

    /**
     * Calculate wallet balance ratio (balance / transaction amount)
     * Critical feature to detect wallet-drain attacks
     */
    public function walletBalanceRatio(): float
    {
        if ($this->amount_kopecks === 0) {
            return 0.0;
        }

        $ratio = ($this->wallet_balance_kopecks ?? 0) / $this->amount_kopecks;
        return min(10.0, max(0.0, $ratio)); // Cap at 10x
    }

    /**
     * Get urgency level as numeric score (0.0 to 1.0)
     * Critical for Medical to reduce false positives on urgent payments
     */
    public function urgencyScore(): float
    {
        return match ($this->urgency_level) {
            'emergency' => 1.0,
            'high' => 0.75,
            'medium' => 0.5,
            'low' => 0.25,
            default => 0.0,
        };
    }

    /**
     * Check if this is a medical emergency payment
     */
    public function isMedicalEmergency(): bool
    {
        return $this->vertical_code === 'medical' 
            && ($this->urgency_level === 'emergency' || $this->is_emergency_payment === true);
    }

    /**
     * Calculate price spike ratio (current / average for this user)
     * Helps identify legitimate large payments vs fraud
     */
    public function priceSpikeRatio(): float
    {
        return $this->consultation_price_spike_ratio ?? 1.0;
    }
}
