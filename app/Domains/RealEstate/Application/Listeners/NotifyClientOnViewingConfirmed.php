<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Domains\RealEstate\Domain\Events\ViewingConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

final class NotifyClientOnViewingConfirmed implements ShouldQueue
{
    use InteractsWithQueue;


    public int $tries = 3;

    public int $backoff = 60;

    public function handle(ViewingConfirmed $event, LoggerInterface $logger): void
    {
        $correlationId = $event->correlationId;

        $logger->info('NotifyClientOnViewingConfirmed: handling event', [
            'viewing_id'     => $event->viewingId->toString(),
            'property_id'    => $event->propertyId->toString(),
            'correlation_id' => $correlationId,
        ]);

        try {
            $this->sendNotification($event, $logger);

            $logger->info('NotifyClientOnViewingConfirmed: notification dispatched', [
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

    private function sendNotification(ViewingConfirmed $event, LoggerInterface $logger): void
    {
        $clientId = $event->clientId;

        if ($clientId === null) {
            return;
        }

        $user = \App\Models\User::find($clientId);

        if ($user === null) {
            $logger->warning('NotifyClientOnViewingConfirmed: client not found', [
                'client_id'      => $clientId,
                'correlation_id' => $event->correlationId,
            ]);

            return;
        }

        $user->notify(new \App\Domains\RealEstate\Application\Notifications\ViewingConfirmedNotification(
            viewingId:   $event->viewingId->toString(),
            propertyId:  $event->propertyId->toString(),
            scheduledAt: $event->scheduledAt,
            correlationId: $event->correlationId,
        ));
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
}
