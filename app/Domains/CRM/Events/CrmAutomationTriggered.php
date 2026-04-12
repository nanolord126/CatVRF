<?php

declare(strict_types=1);

namespace App\Domains\CRM\Events;

use App\Domains\CRM\Models\CrmAutomation;
use App\Domains\CRM\Models\CrmClient;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CrmAutomationTriggered — событие срабатывания автоматизации CRM.
 *
 * Диспатчится из ExecuteCrmAutomationJob после выполнения действия.
 * Подписчики: логирование, аналитика, уведомление менеджеров.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmAutomationTriggered
{
    use \Illuminate\Foundation\Events\Dispatchable;
    use \Illuminate\Broadcasting\InteractsWithSockets;
    use \Illuminate\Queue\SerializesModels;

    /**
     * @param CrmAutomation $automation    Автоматизация, которая сработала
     * @param CrmClient     $client        Клиент, для которого сработала
     * @param string        $correlationId Идентификатор корреляции
     * @param string        $result        Результат выполнения (sent, failed, skipped)
     */
    public function __construct(
        public readonly CrmAutomation $automation,
        public readonly CrmClient $client,
        public readonly string $correlationId,
        public readonly string $result = 'sent',
    ) {
    }

    /**
     * Строковое представление для логирования и отладки.
     */
    public function __toString(): string
    {
        return sprintf(
            'CrmAutomationTriggered[automation_id=%d, client_id=%d, result=%s, correlation_id=%s]',
            $this->automation->id,
            $this->client->id,
            $this->result,
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



