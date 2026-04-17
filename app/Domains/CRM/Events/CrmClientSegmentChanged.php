<?php

declare(strict_types=1);

namespace App\Domains\CRM\Events;

use App\Domains\CRM\Models\CrmClient;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CrmClientSegmentChanged — событие изменения сегмента CRM-клиента.
 *
 * Диспатчится из CrmSegmentationService при автоматической/ручной пересегментации.
 * Подписчики: маркетинговые автоматизации, уведомления менеджеров, аналитика.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmClientSegmentChanged
{

    /**
     * @param CrmClient $client        Клиент, у которого изменился сегмент
     * @param array     $addedSegments Добавленные сегменты (IDs)
     * @param array     $removedSegments Удалённые сегменты (IDs)
     * @param string    $correlationId Идентификатор корреляции
     * @param int       $tenantId      ID тенанта
     */
    public function __construct(
        public readonly CrmClient $client,
        public readonly array $addedSegments,
        public readonly array $removedSegments,
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
            'CrmClientSegmentChanged[client_id=%d, added=%s, removed=%s, correlation_id=%s]',
            $this->client->id,
            implode(',', $this->addedSegments),
            implode(',', $this->removedSegments),
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



