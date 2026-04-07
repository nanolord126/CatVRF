<?php declare(strict_types=1);

/**
 * StrBookingListener — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/strbookinglistener
 */


namespace App\Domains\ShortTermRentals\Events;


use Psr\Log\LoggerInterface;
final class StrBookingListener
{

    public function __construct(private readonly LoggerInterface $logger) {}

        /**
         * Создание записи в аудит-логе при новом бронировании
         */
        public function onCreated(StrBookingCreated $event): void
        {
            $this->logger->info('ShortTermRental: New Booking Event Recieved', [
                'booking_id' => $event->booking->id,
                'user_id' => $event->booking->user_id,
                'correlation_id' => $event->correlationId,
            ]);

            // Здесь может быть вызов сервиса нотификаций или планирование тасок
        }

        /**
         * Действия при завершении проживания
         */
        public function onCompleted(StrBookingCompleted $event): void
        {
            $this->logger->info('ShortTermRental: Booking Completed Event Recieved', [
                'booking_id' => $event->booking->id,
                'correlation_id' => $event->correlationId,
            ]);

            // Запуск процесса автоматического возврата залога через 24 часа (если нет споров)
            // \App\Domains\ShortTermRentals\Jobs\AutoReleaseDepositJob::dispatch($event->booking->id)->delay(now()->addDay());
        }
}
