<?php

declare(strict_types=1);

namespace App\DTO\Security;

use App\Enums\BotRiskLevel;
use Illuminate\Http\Request;

/**
 * Bot Detection Result DTO
 * 
 * Immutable data transfer object for bot detection results.
 * Contains all detection signals and calculated risk level.
 */
final readonly class BotDetectionResult
{
    public function __construct(
        public readonly bool $isBot,
        public readonly BotRiskLevel $riskLevel,
        public readonly float $confidence,
        public readonly string $ipAddress,
        public readonly ?string $userAgent,
        public readonly array $detectionSignals,
        public readonly array $detectionSources,
        public readonly ?string $matchedRule,
        public readonly array $metadata,
        public readonly string $detectedAt,
    ) {}

    /**
     * Create a clean result (not a bot)
     */
    public static function clean(Request $request): self
    {
        return new self(
            isBot: false,
            riskLevel: BotRiskLevel::LOW,
            confidence: 0.0,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            detectionSignals: [],
            detectionSources: [],
            matchedRule: null,
            metadata: [],
            detectedAt: now()->toIso8601String(),
        );
    }

    /**
     * Create a bot detection result
     */
    public static function detected(
        Request $request,
        BotRiskLevel $riskLevel,
        float $confidence,
        array $detectionSignals = [],
        array $detectionSources = [],
        ?string $matchedRule = null,
        array $metadata = []
    ): self {
        return new self(
            isBot: true,
            riskLevel: $riskLevel,
            confidence: $confidence,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            detectionSignals: $detectionSignals,
            detectionSources: $detectionSources,
            matchedRule: $matchedRule,
            metadata: $metadata,
            detectedAt: now()->toIso8601String(),
        );
    }

    /**
     * Check if protection measures should be applied
     */
    public function requiresProtection(): bool
    {
        return $this->isBot && $this->riskLevel !== BotRiskLevel::LOW;
    }

    /**
     * Check if request should be blocked
     */
    public function shouldBlock(): bool
    {
        return $this->riskLevel->requiresBlocking();
    }

    /**
     * Check if challenge should be presented
     */
    public function shouldChallenge(): bool
    {
        return $this->riskLevel->requiresChallenge();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'is_bot' => $this->isBot,
            'risk_level' => $this->riskLevel->value,
            'risk_label' => $this->riskLevel->getLabel(),
            'confidence' => $this->confidence,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'detection_signals' => $this->detectionSignals,
            'detection_sources' => $this->detectionSources,
            'matched_rule' => $this->matchedRule,
            'metadata' => $this->metadata,
            'detected_at' => $this->detectedAt,
            'requires_protection' => $this->requiresProtection(),
            'should_block' => $this->shouldBlock(),
            'should_challenge' => $this->shouldChallenge(),
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isBot: $data['is_bot'] ?? false,
            riskLevel: BotRiskLevel::from($data['risk_level'] ?? BotRiskLevel::LOW->value),
            confidence: $data['confidence'] ?? 0.0,
            ipAddress: $data['ip_address'] ?? '',
            userAgent: $data['user_agent'] ?? null,
            detectionSignals: $data['detection_signals'] ?? [],
            detectionSources: $data['detection_sources'] ?? [],
            matchedRule: $data['matched_rule'] ?? null,
            metadata: $data['metadata'] ?? [],
            detectedAt: $data['detected_at'] ?? now()->toIso8601String(),
        );
    }
}
