<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;

final readonly class SplitPaymentDto
{
    public function __construct(
        public float $amount,
        public string $currency,
        public bool $isB2b,
        public float $sellerSharePercent,
        public float $agentSharePercent,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            amount: (float) $request->input('amount'),
            currency: $request->input('currency', 'RUB'),
            isB2b: $request->has('inn') && $request->has('business_card_id'),
            sellerSharePercent: (float) $request->input('seller_share_percent', 85.0),
            agentSharePercent: (float) $request->input('agent_share_percent', 15.0),
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'is_b2b' => $this->isB2b,
            'seller_share_percent' => $this->sellerSharePercent,
            'agent_share_percent' => $this->agentSharePercent,
        ];
    }
}
