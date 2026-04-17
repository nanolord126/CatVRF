<?php

declare(strict_types=1);

namespace App\Domains\CRM\Events;

use App\Domains\CRM\Models\CrmInteraction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CrmInteractionRecorded — событие записи нового взаимодействия с CRM-клиентом.
 *
 * Диспатчится из CrmService::recordInteraction().
 * Подписчики: пересчёт статистики клиента, триггерные автоматизации.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmInteractionRecorded
{

    /**
     * @param CrmInteraction $interaction   Созданное взаимодействие
     * @param string         $correlationId Идентификатор корреляции
     * @param int            $tenantId      ID тенанта
     */
    public function __construct(
        public readonly CrmInteraction $interaction,
        public readonly string $correlationId,
        public readonly int $tenantId,
    ) {
    }

    /**
     * Строковое представление для логирования и отладки.
     */
    public function __toString(): string
    {
        return sprintf(
            'CrmInteractionRecorded[interaction_id=%d, client_id=%d, type=%s, correlation_id=%s]',
            $this->interaction->id,
            $this->interaction->crm_client_id,
            $this->interaction->type,
            $this->correlationId,
        );
    }

    /**
     * Преобразовать событие в массив для логирования и сериализации.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            $data[$property->getName()] = $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d H:i:s')
                : $value;
        }

        $data['event_class'] = static::class;
        $data['fired_at'] = now()->toIso8601String();

        return $data;
    }

    /**
     * Получить correlation_id для сквозного трейсинга.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}



