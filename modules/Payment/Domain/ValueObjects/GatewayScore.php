<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\ValueObjects;

final readonly class GatewayScore
{
    private function __construct(
        public string $provider,
        public float $score,
        public float $confidence,
        public string $reason,
    ) {}

    public static function create(
        string $provider,
        float $score,
        string $reason,
    ): self {
        return new self(
            provider: $provider,
            score: $score,
            confidence: $score / 100,
            reason: $reason,
        );
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'confidence' => $this->confidence,
            'reason' => $this->reason,
        ];
    }
}
