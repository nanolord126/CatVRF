<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use Illuminate\Support\Collection;

final readonly class BatchDeductionResult
{
    /**
     * @param Collection<int, array{batch_id: int, batch_number: string, quantity: int, expiry_date: string, days_left: int}> $deductedBatches
     */
    public function __construct(
        public int $totalQuantity,
        public Collection $deductedBatches,
        public string $context,
        public array $meta = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_quantity' => $this->totalQuantity,
            'deducted_batches' => $this->deductedBatches->toArray(),
            'context' => $this->context,
            'meta' => $this->meta,
        ];
    }
}
