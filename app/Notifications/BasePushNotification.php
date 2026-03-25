<?php declare(strict_types=1);

namespace App\Notifications;

/**
 * Base class for push notifications (Firebase, OneSignal)
 * 
 * Отправляет push-уведомления на мобильные устройства
 */
abstract class BasePushNotification extends BaseNotification
{
    /**
     * Заголовок push-уведомления
     */
    protected string $title = 'Notification';

    /**
     * Тело push-уведомления
     */
    protected string $body = '';

    /**
     * Иконка (URL или имя ресурса)
     */
    protected ?string $icon = null;

    /**
     * Картинка (большое изображение в push)
     */
    protected ?string $image = null;

    /**
     * Звук уведомления
     */
    protected ?string $sound = 'default';

    /**
     * Цвет (для Android)
     */
    protected ?string $color = null;

    /**
     * Deep link (экран приложения, который открыть)
     */
    protected ?string $deepLink = null;

    /**
     * Количество на бейдже (iOS)
     */
    protected ?int $badge = null;

    /**
     * Категория (для действий)
     */
    protected ?string $category = null;

    /**
     * Данные payload (дополнительные данные для приложения)
     */
    protected array $payload = [];

    /**
     * TTL для push (сек)
     */
    protected int $ttl = 86400; // 1 день

    /**
     * Приоритет (high, normal)
     */
    protected string $priority = 'high';

    /**
     * Требует ли аутентификации
     */
    protected bool $requiresAuth = false;

    /**
     * Конструктор
     */
    public function __construct(
        int $userId,
        int $tenantId,
        array $data = [],
        ?string $correlationId = null,
        array $channels = ['push']
    ) {
        parent::__construct($userId, $tenantId, $data, $correlationId, $channels);
    }

    /**
     * Установить заголовок
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Установить тело
     */
    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Установить иконку
     */
    public function icon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Установить картинку
     */
    public function image(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Установить звук
     */
    public function sound(string $sound): self
    {
        $this->sound = $sound;
        return $this;
    }

    /**
     * Установить цвет
     */
    public function color(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Установить deep link
     */
    public function deepLink(string $link): self
    {
        $this->deepLink = $link;
        return $this;
    }

    /**
     * Установить бейдж (количество)
     */
    public function badge(int $count): self
    {
        $this->badge = $count;
        return $this;
    }

    /**
     * Установить категорию (для действий)
     */
    public function category(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Добавить payload данные
     */
    public function addPayload(string $key, mixed $value): self
    {
        $this->payload[$key] = $value;
        return $this;
    }

    /**
     * Установить весь payload
     */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;
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
     * Установить приоритет
     */
    public function priority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Требовать аутентификацию
     */
    public function requireAuth(): self
    {
        $this->requiresAuth = true;
        return $this;
    }

    /**
     * Получить данные для Firebase
     */
    public function toFirebase(): array
    {
        return [
            'notification' => [
                'title' => $this->title,
                'body' => $this->body,
                'icon' => $this->icon,
                'image' => $this->image,
                'sound' => $this->sound,
                'color' => $this->color,
                'badge' => $this->badge,
            ],
            'data' => array_merge($this->payload, [
                'deep_link' => $this->deepLink,
                'correlation_id' => $this->correlationId,
            ]),
            'android' => [
                'priority' => $this->priority === 'high' ? 'high' : 'normal',
                'ttl' => $this->ttl . 's',
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => $this->priority === 'high' ? '10' : '1',
                    'apns-expiration' => (now()->addSeconds($this->ttl))->timestamp,
                ],
            ],
        ];
    }

    /**
     * Получить данные для БД
     */
    public function toDatabase(): array
    {
        return array_merge(parent::toDatabase(), [
            'channel' => 'push',
            'title' => $this->title,
            'message_preview' => $this->body,
            'deep_link' => $this->deepLink,
        ]);
    }
}
