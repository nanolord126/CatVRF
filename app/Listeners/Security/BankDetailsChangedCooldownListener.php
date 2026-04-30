<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\Enums\CooldownActionType;
use App\Events\Security\BankDetailsChanged;
use App\Services\Security\CooldownService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Bank Details Changed Cooldown Listener
 * 
 * Triggers a cooldown period when bank details are updated.
 */
final class BankDetailsChangedCooldownListener implements ShouldQueue
{
    public function __construct(
        private readonly CooldownService $cooldownService
    ) {}

    public function handle(BankDetailsChanged $event): void
    {
        $this->cooldownService->startCooldown(
            user: $event->user,
            actionType: CooldownActionType::CHANGE_BANK,
            tenantId: $event->tenantId,
            reason: 'Bank details changed - cooldown activated for security',
            metadata: [
                'changed_fields' => $event->changedFields,
                'ip_address' => $event->ipAddress,
            ]
        );
    }
}
