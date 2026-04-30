<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Interfaces;

interface WalletServiceInterface
{
    public function hold(
        int $walletId,
        int $amount,
        string $correlationId,
        string $sourceType,
        ?int $sourceId,
    ): void;

    public function credit(
        int $walletId,
        int $amount,
        string $type,
        string $correlationId,
        string $sourceType,
        ?int $sourceId,
    ): void;
}
