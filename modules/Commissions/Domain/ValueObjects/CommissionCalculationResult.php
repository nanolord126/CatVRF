<?php

declare(strict_types=1);

namespace Modules\Commissions\Domain\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

final readonly class CommissionCalculationResult implements Arrayable, Jsonable
{
    public function __construct(
        public int $commissionAmount,
        public int $transactionId
    ) {
        if ($this->commissionAmount < 0) {
            throw new \InvalidArgumentException('Commission amount cannot be negative.');
        }
        if ($this->transactionId <= 0) {
            throw new \InvalidArgumentException('Transaction ID must be a positive integer.');
        }
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'commission_amount' => $this->commissionAmount,
            'transaction_id' => $this->transactionId,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
