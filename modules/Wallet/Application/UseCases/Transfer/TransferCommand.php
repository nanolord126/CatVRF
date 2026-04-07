<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\UseCases\Transfer;

final readonly class TransferCommand
{
    public function __construct(
        public int    $fromUserId,
        public int    $toUserId,
        public int    $tenantId,
        public int    $amountKopeks,
        public string $description,
        public string $correlationId,
    ) {}
}
