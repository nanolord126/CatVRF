<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\DTOs;

final readonly class VeganBoxSubscriptionDto
{
    public function __construct(
        public int $userId,
        public int $boxId,
        public string $planType,
        public array $exclusionAllergens = [],
        public ?string $promoCode = null,
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'box_id' => $this->boxId,
            'plan_type' => $this->planType,
            'exclusion_allergens' => $this->exclusionAllergens,
            'promo_code' => $this->promoCode,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            boxId: $data['box_id'],
            planType: $data['plan_type'],
            exclusionAllergens: $data['exclusion_allergens'] ?? [],
            promoCode: $data['promo_code'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
