<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repositories;

use Modules\Payment\Domain\Entities\Payment;
use Modules\Payment\Domain\ValueObjects\PaymentId;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;
    public function findById(PaymentId $id): ?Payment;
    public function findByUuid(string $uuid): ?Payment;
    public function findByPayable(string $payableType, int $payableId): array;
    public function delete(PaymentId $id): void;
}
