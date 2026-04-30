<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\Enums\CooldownActionType;
use App\Events\Security\NewDeviceLogin;
use App\Services\Security\CooldownService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * New Device Login Cooldown Listener
 * 
 * Triggers a cooldown period when a user logs in from a new device.
 */
final class NewDeviceLoginCooldownListener implements ShouldQueue
{
    public function __construct(
        private readonly CooldownService $cooldownService
    ) {}

    public function handle(NewDeviceLogin $event): void
    {
        $this->cooldownService->startCooldown(
            user: $event->user,
            actionType: CooldownActionType::NEW_DEVICE,
            reason: 'Login from new device - cooldown activated for security',
            metadata: [
                'device_id' => $event->device->id,
                'device_name' => $event->device->device_name,
                'device_type' => $event->device->device_type,
                'ip_address' => $event->ipAddress,
            ]
        );
    }
}
