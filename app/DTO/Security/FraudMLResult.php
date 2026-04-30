<?php

declare(strict_types=1);

namespace App\DTO\Security;

use Illuminate\Http\Request;

/**
 * Fraud ML Result DTO
 *
 * Contains the result of fraud ML prediction including:
 * - Fraud score (0-1)
 * - Risk level (low, medium, high)
 * - Action to take (block, challenge, allow)
 * - Feature values
 * - Explanation (SHAP-like values)
 * - Correlation ID for tracing
 * - Latency metrics
 */
final readonly class FraudMLResult
{
    private function __construct(
        public float $fraudScore,
        public string $riskLevel,
        public array $action,
        public array $features,
        public array $explanation,
        public string $correlationId,
        public float $latencyMs,
        public bool $failed = false,
        public ?string $errorMessage = null,
    ) {}

    /**
     * Create a successful fraud ML result
     */
    public static function create(
        float $fraudScore,
        string $riskLevel,
        array $action,
        array $features,
        array $explanation,
        string $correlationId,
        float $latencyMs,
    ): self {
        return new self(
            fraudScore: $fraudScore,
            riskLevel: $riskLevel,
            action: $action,
            features: $features,
            explanation: $explanation,
            correlationId: $correlationId,
            latencyMs: $latencyMs,
            failed: false,
            errorMessage: null,
        );
    }

    /**
     * Create a failed fraud ML result (fail open)
     */
    public static function failOpen(string $correlationId, string $errorMessage): self
    {
        return new self(
            fraudScore: 0.0,
            riskLevel: 'low',
            action: [
                'should_block' => false,
                'should_challenge' => false,
                'should_apply_protection' => false,
                'cooldown_action' => null,
                'cooldown_hours' => 0,
                'reason' => 'ML prediction failed - fail open',
            ],
            features: [],
            explanation: [],
            correlationId: $correlationId,
            latencyMs: 0.0,
            failed: true,
            errorMessage: $errorMessage,
        );
    }

    /**
     * Check if the request should be blocked
     */
    public function shouldBlock(): bool
    {
        return $this->action['should_block'] ?? false;
    }

    /**
     * Check if the request should be challenged
     */
    public function shouldChallenge(): bool
    {
        return $this->action['should_challenge'] ?? false;
    }

    /**
     * Check if protection measures should be applied
     */
    public function shouldApplyProtection(): bool
    {
        return $this->action['should_apply_protection'] ?? false;
    }

    /**
     * Check if the prediction failed
     */
    public function isFailed(): bool
    {
        return $this->failed;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'fraud_score' => $this->fraudScore,
            'risk_level' => $this->riskLevel,
            'action' => $this->action,
            'features' => $this->sanitizeFeatures($this->features),
            'explanation' => $this->explanation,
            'correlation_id' => $this->correlationId,
            'latency_ms' => $this->latencyMs,
            'failed' => $this->failed,
            'error_message' => $this->errorMessage,
        ];
    }

    /**
     * Sanitize features to remove sensitive data
     */
    private function sanitizeFeatures(array $features): array
    {
        $sanitized = $features;

        // Mask IP addresses
        if (isset($sanitized['device']['ip_address'])) {
            $sanitized['device']['ip_address'] = $this->maskIp($sanitized['device']['ip_address']);
        }

        // Mask user agent (keep browser/OS only)
        if (isset($sanitized['device']['user_agent'])) {
            $sanitized['device']['user_agent'] = $this->maskUserAgent($sanitized['device']['user_agent']);
        }

        return $sanitized;
    }

    /**
     * Mask IP address for logging
     */
    private function maskIp(string $ip): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return "{$parts[0]}.{$parts[1]}.***.***";
        }

        return '***.***.***.***';
    }

    /**
     * Mask user agent for logging
     */
    private function maskUserAgent(string $userAgent): string
    {
        // Keep only browser and OS, remove version numbers and other details
        if (preg_match('/(Chrome|Firefox|Safari|Edge)/i', $userAgent, $browserMatch)) {
            if (preg_match('/(Windows|macOS|Linux|Android|iOS)/i', $userAgent, $osMatch)) {
                return "{$browserMatch[1]} on {$osMatch[1]}";
            }
            return $browserMatch[1];
        }

        return 'Unknown Browser';
    }
}
