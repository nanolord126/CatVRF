<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;


use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: запись на услугу создана.
 * Публикуется в приватный канал tenant.
 */
final class AppointmentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $appointmentId,
        public readonly int    $clientId,
        public readonly int    $masterId,
        public readonly int    $tenantId,
        public readonly string $scheduledAt,
        public readonly int    $priceKopecks,
        public readonly string $correlationId,
    ) {}

    /**
     * Возвращает список broadcast-каналов.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('beauty.appointments.' . $this->tenantId),
        ];
    }

    /** Имя broadcast-события. */
    public function broadcastAs(): string
    {
        return 'beauty.appointment.created';
    }

    /**
     * Correlation ID для сквозной трассировки.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Представление события для audit-лога.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'client_id'      => $this->clientId,
            'master_id'      => $this->masterId,
            'tenant_id'      => $this->tenantId,
            'scheduled_at'   => $this->scheduledAt,
            'price_kopecks'  => $this->priceKopecks,
            'correlation_id' => $this->correlationId,
            'event'          => 'beauty.appointment.created',
            'timestamp'      => Carbon::now()->toIso8601String(),
        ];
    }
}

