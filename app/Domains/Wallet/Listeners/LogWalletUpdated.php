<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Listeners;

use App\Domains\Wallet\Events\WalletUpdated;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use App\Domains\Wallet\Models\Wallet;

/**
 * Слушатель: логирование обновления кошелька.
 *
 * Записывает событие в audit-лог с полным контекстом и diff (old/new values).
 */
final class LogWalletUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    public function handle(WalletUpdated $event): void
    {
        $this->logger->$this->logger->info('Wallet updated', [
            'wallet_id' => $event->wallet->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->wallet->tenant_id,
            'changed_fields' => array_keys($event->newValues),
        ]);

        $this->audit->record(
            action: 'wallet_updated',
            subjectType: Wallet::class,
            subjectId: $event->wallet->id,
            correlationId: $event->correlationId,
            oldValues: $event->oldValues,
            newValues: $event->newValues,
        );
    }
}
