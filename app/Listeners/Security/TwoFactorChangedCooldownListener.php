<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\Enums\CooldownActionType;
use App\Events\Security\TwoFactorChanged;
use App\Services\Security\CooldownService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Two-Factor Authentication Changed Cooldown Listener
 * 
 * Triggers a cooldown period when a user changes 2FA settings.
 */
final class TwoFactorChangedCooldownListener implements ShouldQueue
{
    public function __construct(
        private readonly CooldownService $cooldownService
    ) {}

    public function handle(TwoFactorChanged $event): void
    {
        $this->cooldownService->startCooldown(
            user: $event->user,
            actionType: CooldownActionType::TWO_FA_CHANGE,
            reason: "2FA {$event->action} - cooldown activated for security",
            metadata: [
                'action' => $event->action,
                'ip_address' => $event->ipAddress,
                'user_agent' => $event->userAgent,
            ]
        );
    }
}
