<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Repositories;

use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\ValueObjects\IdempotencyKey;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;
    // public function saveEvents(Payment $payment): void; // Сохранение в Outbox или мгновенный dispatch
    public function findById(string $id): ?Payment;
    public function findByProviderId(string $providerPaymentId): ?Payment;
    public function findByIdempotencyKey(int $tenantId, IdempotencyKey $key): ?Payment;
}
