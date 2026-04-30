<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use App\Events\Security\PasskeyRevoked;
use App\Services\Security\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;

final class HandlePasskeyRevoked implements ShouldQueue
{
    public function __construct(private readonly ChannelManager $notificationManager,
        private readonly BusDispatcher $bus,
        private readonly AuditService $auditService,) {}

    public function handle(PasskeyRevoked $event): void
    {
        $user = $event->user;
        $credential = $event->credential;

        $this->log->warning('Passkey revoked', [
            'user_id' => $user->id,
            'credential_id' => $credential->id,
            'reason' => $event->reason,
        ]);

        // Check if user has any remaining credentials
        $remainingCredentials = $user->webauthnCredentials()
            ->where('tenant_id', $user->tenant_id)
            ->where('id', '!=', $credential->id)
            ->where('is_compromised', false)
            ->count();

        if ($remainingCredentials === 0) {
            $this->log->critical('User has no valid passkeys remaining', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ]);

            // TODO: Send urgent notification to user
            // $this->notificationManager->send($user, new NoPasskeysRemainingNotification());

            // TODO: Alert security team
            // SecurityTeamAlert::$this->bus->dispatch($event);
        }

        $this->auditService->logEvent('passkey_revoked', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'credential_id' => $credential->id,
            'reason' => $event->reason,
            'remaining_credentials' => $remainingCredentials,
        ], 'security');
    }
}
