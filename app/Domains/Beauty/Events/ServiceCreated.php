<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: новая услуга добавлена в каталог салона.
 */
final class ServiceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $serviceId,
        public readonly int    $salonId,
        public readonly int    $tenantId,
        public readonly int    $priceKopecks,
        public readonly string $correlationId,
    ) {}

    /** @return array<int, \Illuminate\Broadcasting\Channel> */
    public function broadcastOn(): array
    {
        return [];
    }

    /**
     * Имя broadcast-события.
     */
    public function broadcastAs(): string
    {
        return 'beauty.service.created';
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
            'service_id'     => $this->serviceId,
            'salon_id'       => $this->salonId,
            'tenant_id'      => $this->tenantId,
            'price_kopecks'  => $this->priceKopecks,
            'correlation_id' => $this->correlationId,
        ];
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . $this->correlationId;
    }
}

