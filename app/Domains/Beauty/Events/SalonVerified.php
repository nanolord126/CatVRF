<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: салон прошёл модерацию.
 * Триггерирует публикацию салона в публичной витрине.
 */
final class SalonVerified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $salonId,
        public readonly int    $tenantId,
        public readonly string $correlationId,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('beauty.salons.' . $this->tenantId),
        ];
    }

    /**
     * Handle broadcastAs operation.
     *
     * @throws \DomainException
     */
    public function broadcastAs(): string
    {
        return 'beauty.salon.verified';
    }

    /**
     * Получить correlationId этого события.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Представление события в виде массива для логирования/аудита.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'salon_id'       => $this->salonId,
            'tenant_id'      => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ];
    }
}

