<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\Enums\CooldownActionType;
use App\Events\Security\PasswordChanged;
use App\Services\Security\CooldownService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Password Changed Cooldown Listener
 * 
 * Triggers a cooldown period when a user changes their password.
 */
final class PasswordChangedCooldownListener implements ShouldQueue
{
    public function __construct(
        private readonly CooldownService $cooldownService
    ) {}

    public function handle(PasswordChanged $event): void
    {
        $this->cooldownService->startCooldown(
            user: $event->user,
            actionType: CooldownActionType::PASSWORD_CHANGE,
            reason: 'Password changed - cooldown activated for security',
            metadata: [
                'ip_address' => $event->ipAddress,
                'user_agent' => $event->userAgent,
            ]
        );
    }
}
