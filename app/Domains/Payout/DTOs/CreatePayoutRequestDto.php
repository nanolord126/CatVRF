<?php declare(strict_types=1);

namespace App\Domains\Payout\DTOs;

final readonly class CreatePayoutRequestDto
{
    public function __construct(
        public int $tenantId,
        public int $businessGroupId,
        public int $amount,
        public array $bankDetails,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'],
            amount: $data['amount'],
            bankDetails: $data['bank_details'],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'amount' => $this->amount,
            'bank_details' => $this->bankDetails,
        ];
    }
}
