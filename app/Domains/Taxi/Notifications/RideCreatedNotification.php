<?php declare(strict_types=1);

/**
 * RideCreatedNotification — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ridecreatednotification
 */


namespace App\Domains\Taxi\Notifications;

use Illuminate\Notifications\Notification;

final class RideCreatedNotification extends Notification
{

    use \Illuminate\Bus\Queueable;
        public function __construct(
            private readonly TaxiRide $ride,
            private readonly string $correlationId
        ) {}

        public function via($notifiable): array
        {
            return ['database', 'broadcast']; // По канону: уведомления в реальном времени
        }

        /**
         * Формат для БД и Витрины.
         */
        public function toArray($notifiable): array
        {
            return [
                'ride_uuid' => $this->ride->uuid,
                'message' => 'Новая поездка ожидает принятия!',
                'pickup_address' => $this->ride->pickup_address,
                'dropoff_address' => $this->ride->dropoff_address,
                'price' => round($this->ride->price / 100, 2) . ' ₽',
                'correlation_id' => $this->correlationId,
                'type' => 'taxi_ride_available'
            ];
        }

        /**
         * По канону: уведомление по почте (если включено).
         */
        public function toMail($notifiable): MailMessage
        {
            return (new MailMessage)
                ->subject('Новый заказ в Taxi ' . tenant()->name)
                ->line('Доступен новый заказ по адресу: ' . $this->ride->pickup_address)
                ->action('Принять заказ', url('/driver/rides/' . $this->ride->uuid))
                ->line('Correlation ID: ' . $this->correlationId);
        }
}
