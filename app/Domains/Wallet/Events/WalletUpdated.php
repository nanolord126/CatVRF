<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Events;

use App\Domains\Wallet\Models\Wallet;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Доменное событие: кошелёк обновлён (balance changed).
 *
 * Содержит oldValues/newValues для аудита.
 */
final class WalletUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Wallet $wallet,
        public readonly string $correlationId,
        public readonly ?int $userId = null,
        public readonly array $oldValues = [],
        public readonly array $newValues = [],
    ) {}

    /** Контекст для AuditService. */
    public function toAuditContext(): array
    {
        return [
            'event' => 'wallet_updated',
            'wallet_id' => $this->wallet->id,
            'tenant_id' => $this->wallet->tenant_id,
            'business_group_id' => $this->wallet->business_group_id,
            'correlation_id' => $this->correlationId,
            'user_id' => $this->userId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
        ];
    }

    /** Изменилось ли конкретное поле. */
    public function hasChanged(string $field): bool
    {
        return array_key_exists($field, $this->newValues);
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
