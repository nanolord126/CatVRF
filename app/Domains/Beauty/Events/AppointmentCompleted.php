<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;


use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: услуга выполнена.
 * Триггерит выплату мастеру и начисление баллов лояльности.
 */
final class AppointmentCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $appointmentId,
        public readonly int    $masterId,
        public readonly int    $clientId,
        public readonly int    $tenantId,
        public readonly int    $payoutKopecks,
        public string $correlationId = '',
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('beauty.appointments.' . $this->tenantId),
        ];
    }

    /**
     * Handle broadcastAs operation.
     *
     * @throws \DomainException
     */
    public function broadcastAs(): string
    {
        return 'beauty.appointment.completed';
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
            'master_id'      => $this->masterId,
            'client_id'      => $this->clientId,
            'tenant_id'      => $this->tenantId,
            'payout_kopecks' => $this->payoutKopecks,
            'correlation_id' => $this->correlationId,
            'event'          => 'beauty.appointment.completed',
            'timestamp'      => Carbon::now()->toIso8601String(),
        ];
    }
}

