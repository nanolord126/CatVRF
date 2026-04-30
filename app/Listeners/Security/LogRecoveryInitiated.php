<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Psr\Log\LoggerInterface;

use App\Events\Security\RecoveryInitiated;
use App\Services\Security\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;

final class LogRecoveryInitiated implements ShouldQueue
{
    public function __construct(private readonly BusDispatcher $bus,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,) {}

    public function handle(RecoveryInitiated $event): void
    {
        $user = $event->user;
        $recoveryLog = $event->recoveryLog;

        $this->log->$this->logger->info('Recovery initiated', [
            'user_id' => $user->id,
            'method' => $recoveryLog->method,
            'risk_score' => $recoveryLog->risk_score,
        ]);

        // Additional logging for high-risk recoveries
        if ($recoveryLog->isHighRisk()) {
            $this->log->warning('High-risk recovery initiated', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'risk_score' => $recoveryLog->risk_score,
                'ip_address' => $recoveryLog->ip_address,
            ]);

            // TODO: Alert security team
            // SecurityTeamAlert::$this->bus->dispatch($event);
        }
    }
}
