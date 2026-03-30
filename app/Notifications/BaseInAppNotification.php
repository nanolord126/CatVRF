<?php declare(strict_types=1);

namespace App\Notifications;

/**
 * Base class for in-app notifications (Database channel + WebSocket delivery)
 *
 * Хранится в БД и отправляется пользователю через WebSocket real-time
 */
abstract class BaseInAppNotification extends BaseNotification
{
    /**
     * Заголовок уведомления
     */
    protected string $title = 'Notification';

    /**
     * Содержимое уведомления
     */
    protected string $message = '';

    /**
     * Тип уведомления (info, success, warning, error, action)
     */
    protected string $notificationType = 'info';

    /**
     * Иконка для отображения
     */
    protected ?string $icon = null;

    /**
     * URL для изображения
     */
    protected ?string $imageUrl = null;

    /**
     * Action button (текст и ссылка)
     */
    protected array $actionButton = [];

    /**
     * Второй action button
     */
    protected array $secondaryButton = [];

    /**
     * Данные для frontend (JSON)
     */
    protected array $frontendData = [];

    /**
     * Вы должны подтвердить это уведомление
     */
    protected bool $requiresConfirmation = false;

    /**
     * Таймер автозакрытия (мс, 0 = не закрывать)
     */
    protected int $autoCloseTimeout = 5000;

    /**
     * Показывать ли в истории
     */
    protected bool $showInHistory = true;

    /**
     * Конструктор
     */
    public function __construct(
        int $userId,
        int $tenantId,
        array $data = [],
        ?string $correlationId = null,
        array $channels = ['database']
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
     * Установить сообщение
     */
    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Установить тип уведомления (info, success, warning, error, action)
     */
    public function type(string $type): self
    {
        $this->notificationType = $type;
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
     * Установить URL картинки
     */
    public function image(string $url): self
    {
        $this->imageUrl = $url;
        return $this;
    }

    /**
     * Добавить кнопку действия
     */
    public function withAction(string $label, string $url, string $style = 'primary'): self
    {
        $this->actionButton = [
            'label' => $label,
            'url' => $url,
            'style' => $style,
        ];
        return $this;
    }

    /**
     * Добавить вторую кнопку
     */
    public function withSecondaryAction(string $label, string $url, string $style = 'secondary'): self
    {
        $this->secondaryButton = [
            'label' => $label,
            'url' => $url,
            'style' => $style,
        ];
        return $this;
    }

    /**
     * Требовать подтверждение
     */
    public function requireConfirmation(): self
    {
        $this->requiresConfirmation = true;
        return $this;
    }

    /**
     * Установить таймер автозакрытия
     */
    public function autoClose(int $milliseconds): self
    {
        $this->autoCloseTimeout = $milliseconds;
        return $this;
    }

    /**
     * Скрыть из истории
     */
    public function hideFromHistory(): self
    {
        $this->showInHistory = false;
        return $this;
    }

    /**
     * Добавить frontend данные
     */
    public function addFrontendData(string $key, mixed $value): self
    {
        $this->frontendData[$key] = $value;
        return $this;
    }

    /**
     * Получить данные для БД (database channel)
     */
    public function toDatabase(): array
    {
        return array_merge(parent::toDatabase(), [
            'title' => $this->title,
            'message' => $this->message,
            'notification_type' => $this->notificationType,
            'icon' => $this->icon,
            'image_url' => $this->imageUrl,
            'action_button' => $this->actionButton,
            'secondary_button' => $this->secondaryButton,
            'frontend_data' => $this->frontendData,
            'requires_confirmation' => $this->requiresConfirmation,
            'auto_close_timeout' => $this->autoCloseTimeout,
            'show_in_history' => $this->showInHistory,
            'channel' => 'in_app',
        ]);
    }

    /**
     * Получить данные для WebSocket broadcast
     */
    public function toWebSocket(): array
    {
        return [
            'event' => 'notification:' . $this->type,
            'notification' => $this->toDatabase(),
            'correlation_id' => $this->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Получить заголовок
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Получить сообщение
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
