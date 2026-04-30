<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repositories;

use Modules\Payment\Domain\Entities\FiscalReceipt;
use Carbon\CarbonImmutable;

interface FiscalReceiptRepositoryInterface
{
    public function save(FiscalReceipt $receipt): void;
    
    public function findByUuid(string $uuid): ?FiscalReceipt;
    
    public function findByPaymentIntentUuid(string $paymentIntentUuid): array;
    
    public function findByOrderId(string $orderId): array;
    
    public function findPending(int $limit = 100): array;
    
    public function findFailed(int $limit = 100): array;
    
    public function deleteOlderThan(CarbonImmutable $date): int;
}
