<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Listeners;

use App\Domains\Wallet\Events\WalletCreated;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Слушатель: логирование создания кошелька.
 *
 * Записывает событие в audit-лог с полным контекстом (correlation_id, tenant_id).
 */
final class LogWalletCreated
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    public function handle(WalletCreated $event): void
    {
        $this->logger->info('Wallet created', [
            'wallet_id' => $event->wallet->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->wallet->tenant_id,
        ]);

        $this->audit->record(
            action: 'wallet_created',
            subjectType: \App\Domains\Wallet\Models\Wallet::class,
            subjectId: $event->wallet->id,
            correlationId: $event->correlationId,
            newValues: $event->wallet->toArray(),
        );
    }
}
