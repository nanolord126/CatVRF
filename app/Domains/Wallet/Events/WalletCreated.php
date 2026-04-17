<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Events;

use App\Domains\Wallet\Models\Wallet;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Доменное событие: кошелёк создан.
 *
 * Все свойства — public readonly для доступа из Listeners.
 * Обязательно содержит correlation_id для полной трассировки.
 */
final class WalletCreated
{

    public function __construct(
        public readonly Wallet $wallet,
        public readonly string $correlationId,
        public readonly ?int $userId = null,
    ) {}

    /** Контекст для AuditService. */
    public function toAuditContext(): array
    {
        return [
            'event' => 'wallet_created',
            'wallet_id' => $this->wallet->id,
            'tenant_id' => $this->wallet->tenant_id,
            'business_group_id' => $this->wallet->business_group_id,
            'correlation_id' => $this->correlationId,
            'user_id' => $this->userId,
        ];
    }

    public function getTenantId(): int
    {
        return $this->wallet->tenant_id;
    }

    public function getBusinessGroupId(): ?int
    {
        return $this->wallet->business_group_id;
    }
}
