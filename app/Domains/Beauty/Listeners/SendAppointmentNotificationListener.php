<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\Events\AppointmentBookedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;

final class SendAppointmentNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly NotificationService $notificationService,
        private readonly LogManager $log,) {}

    public function handle(AppointmentBookedEvent $event): void
    {
        try {
            $this->notificationService->send(
                $event->getUserId(),
                'beauty_appointment_booked',
                [
                    'appointment_id' => $event->getAppointmentId(),
                    'salon_id' => $event->getSalonId(),
                    'master_id' => $event->getMasterId(),
                    'total_price' => $event->getTotalPrice(),
                    'is_b2b' => $event->isB2b(),
                ],
                correlationId: $event->correlationId,
            );

            $this->log->channel('audit')->$this->logger->info('beauty.appointment.notification.sent', [
                'correlation_id' => $event->correlationId,
                'appointment_id' => $event->getAppointmentId(),
                'user_id' => $event->getUserId(),
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('beauty.appointment.notification.failed', [
                'correlation_id' => $event->correlationId,
                'appointment_id' => $event->getAppointmentId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(AppointmentBookedEvent $event, \Throwable $exception): void
    {
        $this->log->channel('audit')->error('beauty.appointment.notification.queue.failed', [
            'correlation_id' => $event->correlationId,
            'appointment_id' => $event->getAppointmentId(),
            'error' => $exception->getMessage(),
        ]);
    }
}
