<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Notifications;

use App\Domains\Taxi\Models\TaxiRide;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026: RideCreatedNotification (Уведомления).
 * Слой 9: События, Джобы и Нотификации.
 */
final class RideCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
