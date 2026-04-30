<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\Listeners;

use App\Domains\RealEstate\Domain\Events\ViewingConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use App\Domains\RealEstate\Application\Notifications\ViewingConfirmedNotification;
use App\Models\User;

final class NotifyClientOnViewingConfirmed implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 60;

    public function handle(ViewingConfirmed $event, LoggerInterface $logger): void
    {
        $correlationId = $event->correlationId;

        $logger->$this->logger->info('NotifyClientOnViewingConfirmed: handling event', [
            'viewing_id'     => $event->viewingId->toString(),
            'property_id'    => $event->propertyId->toString(),
            'correlation_id' => $correlationId,
        ]);

        try {
            $this->sendNotification($event, $logger);

            $logger->$this->logger->info('NotifyClientOnViewingConfirmed: notification dispatched', [
                'viewing_id'      => $event->viewingId->toString(),
                'scheduled_at'    => $event->scheduledAt->format('Y-m-d H:i:s'),
                'correlation_id'  => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $logger->error('NotifyClientOnViewingConfirmed: failed to notify', [
                'viewing_id'     => $event->viewingId->toString(),
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    public function failed(ViewingConfirmed $event, \Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'NotifyClientOnViewingConfirmed permanently failed [viewing_id=%s, correlation_id=%s]: %s',
                $event->viewingId->toString(),
                $event->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }

    private function sendNotification(ViewingConfirmed $event, LoggerInterface $logger): void
    {
        $clientId = $event->clientId;

        if ($clientId === null) {
            return;
        }

        $user = User::find($clientId);

        if ($user === null) {
            $logger->warning('NotifyClientOnViewingConfirmed: client not found', [
                'client_id'      => $clientId,
                'correlation_id' => $event->correlationId,
            ]);

            return;
        }

        $user->notify(new ViewingConfirmedNotification(
            viewingId:   $event->viewingId->toString(),
            propertyId:  $event->propertyId->toString(),
            scheduledAt: $event->scheduledAt,
            correlationId: $event->correlationId,
        ));
    }
}
