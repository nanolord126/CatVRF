<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use App\Events\Security\AccountLocked;
use App\Services\Security\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;

final class SendAccountLockedNotification implements ShouldQueue
{
    public function __construct(private readonly ChannelManager $notificationManager,
        private readonly BusDispatcher $bus,
        private readonly AuditService $auditService,) {}

    public function handle(AccountLocked $event): void
    {
        $user = $event->user;

        $this->log->warning('Sending account locked notification', [
            'user_id' => $user->id,
            'reason' => $event->reason,
        ]);

        // TODO: Send email notification
        // $this->notificationManager->send($user, new AccountLockedNotification($event->reason));

        // TODO: Send SMS if available
        // if ($user->phone) {
        //     SmsService::send($user->phone, 'Your account has been locked for security reasons.');
        // }

        // TODO: Send to security team for monitoring
        // SecurityTeamNotification::$this->bus->dispatch($event);

        $this->auditService->logEvent('account_locked_notification_sent', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'reason' => $event->reason,
        ], 'security');
    }
}
