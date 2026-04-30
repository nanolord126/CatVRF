<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repositories;

use Modules\Payment\Domain\Entities\Payout;

interface PayoutRepositoryInterface
{
    public function create(array $data): Payout;

    public function update(int $id, array $data): Payout;

    public function findById(int $id): ?Payout;

    public function findBySellerId(int $sellerId, ?string $status = null): array;

    public function findByPaymentRecordId(int $paymentRecordId): array;
}
