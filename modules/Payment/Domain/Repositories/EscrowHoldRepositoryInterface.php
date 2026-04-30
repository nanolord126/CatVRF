<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repositories;

use Modules\Payment\Domain\Entities\EscrowHold;

interface EscrowHoldRepositoryInterface
{
    public function create(array $data): EscrowHold;

    public function update(int $id, array $data): EscrowHold;

    public function findByUuid(string $uuid): ?EscrowHold;

    public function findActiveByWallet(int $walletId): array;

    public function findExpired(): array;
}
