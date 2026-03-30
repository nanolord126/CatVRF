<?php declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Base class for all notifications in CatVRF platform
 *
 * Обязательные поля в каждом notification:
 * - correlation_id: UUID для трейсинга
 * - tenant_id: изоляция по тенанту
 * - user_id: кто получает уведомление
 * - channels: array of delivery channels (mail, sms, push, etc)
 *
 * @OpenApi\Schema(
 *     schema="Notification",
 *     type="object",
 *     required={"id", "user_id", "type", "status"},
 *     @OpenApi\Property(property="id", type="string", format="uuid"),
 *     @OpenApi\Property(property="user_id", type="integer"),
 *     @OpenApi\Property(property="type", type="string", enum={"payment", "order", "appointment", "referral"}),
 *     @OpenApi\Property(property="status", type="string", enum={"pending", "sent", "delivered", "failed"}),
 *     @OpenApi\Property(property="correlation_id", type="string", format="uuid"),
 *     @OpenApi\Property(property="created_at", type="string", format="date-time"),
 *     @OpenApi\Property(property="sent_at", type="string", format="date-time", nullable=true)
 * )
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * UUID для трейсинга через систему
     */
    protected string $correlationId;

    /**
     * Tenant ID для изоляции данных
     */
    protected int $tenantId;

    /**
     * User ID получателя
     */
    protected int $userId;

    /**
     * Тип уведомления (payment, order, appointment, etc)
     */
    protected string $type = 'generic';

    /**
     * Приоритет доставки (high, normal, low)
     */
    protected string $priority = 'normal';

    /**
     * Каналы доставки (mail, sms, push, database, web)
     */
    protected array $channels = ['database'];

    /**
     * Данные для шаблонов
     */
    protected array $data = [];

    /**
     * Время жизни уведомления (сек)
     */
    protected ?int $ttl = null;

    /**
     * Попытки отправки
     */
    protected int $maxAttempts = 3;

    /**
     * Задержка между попытками (сек)
     */
    protected int $backoffDelay = 300;

    /**
     * Проверка дозволения уведомления (opt-out)
     */
    protected bool $checkPreferences = true;

    /**
     * Конструктор
     */
    public function __construct(
        int $userId,
        int $tenantId,
        array $data = [],
        ?string $correlationId = null,
        array $channels = []
    ) {
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->data = $data;
        $this->correlationId = $correlationId ?? Str::uuid()->toString();

        if (!empty($channels)) {
            $this->channels = $channels;
        }
    }

    /**
     * Получить каналы доставки
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Получить данные уведомления
     */
    public function getData(): array
    {
        return array_merge($this->data, [
            'type' => $this->type,
            'priority' => $this->priority,
            'correlation_id' => $this->correlationId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'sent_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Получить данные для БД (database channel)
     */
    public function toDatabase(): array
    {
        return [
            'type' => $this->type,
            'priority' => $this->priority,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $this->tenantId,
            'data' => $this->data,
            'read_at' => null,
        ];
    }

    /**
     * Установить correlation ID
     */
    public function withCorrelationId(string $id): self
    {
        $this->correlationId = $id;
        return $this;
    }

    /**
     * Установить каналы доставки
     */
    public function via(...$channels): self
    {
        $this->channels = is_array($channels[0]) ? $channels[0] : $channels;
        return $this;
    }

    /**
     * Добавить канал доставки
     */
    public function addChannel(string $channel): self
    {
        $this->channels[] = $channel;
        return $this;
    }

    /**
     * Установить приоритет
     */
    public function priority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Установить TTL
     */
    public function ttl(int $seconds): self
    {
        $this->ttl = $seconds;
        return $this;
    }

    /**
     * Установить макс попыток
     */
    public function tries(int $count): self
    {
        $this->maxAttempts = $count;
        return $this;
    }

    /**
     * Установить задержку между попытками
     */
    public function backoff(int $seconds): self
    {
        $this->backoffDelay = $seconds;
        return $this;
    }

    /**
     * Отключить проверку предпочтений
     */
    public function skipPreferenceCheck(): self
    {
        $this->checkPreferences = false;
        return $this;
    }

    /**
     * Получить correlation ID
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Получить tenant ID
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * Получить user ID
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Получить тип уведомления
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Должна ли проверяться категория предпочтений
     */
    public function shouldCheckPreferences(): bool
    {
        return $this->checkPreferences;
    }
}
