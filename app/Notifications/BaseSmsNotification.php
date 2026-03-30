<?php declare(strict_types=1);

namespace App\Notifications;

/**
 * Base class for SMS notifications (Twilio/Vonage)
 *
 * Отправляет SMS на номер телефона пользователя
 */
abstract class BaseSmsNotification extends BaseNotification
{
    /**
     * Шаблон SMS сообщения
     */
    protected string $template = 'sms.generic';

    /**
     * Текст сообщения (для простых SMS)
     */
    protected ?string $message = null;

    /**
     * Телефон получателя (переопределять или брать из модели)
     */
    protected ?string $phone = null;

    /**
     * Переменные для подстановки в шаблон
     */
    protected array $variables = [];

    /**
     * Приоритет доставки (high, normal, low)
     */
    protected string $priority = 'normal';

    /**
     * Максимальное количество символов (для услугового SMS)
     */
    protected int $maxChars = 160;

    /**
     * Конструктор
     */
    public function __construct(
        int $userId,
        int $tenantId,
        array $data = [],
        ?string $correlationId = null,
        array $channels = ['sms']
    ) {
        parent::__construct($userId, $tenantId, $data, $correlationId, $channels);
    }

    /**
     * Установить текст сообщения
     */
    public function message(string $text): self
    {
        $this->message = $text;
        return $this;
    }

    /**
     * Установить номер телефона
     */
    public function phone(string $number): self
    {
        $this->phone = $number;
        return $this;
    }

    /**
     * Установить шаблон
     */
    public function template(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Установить переменные для шаблона
     */
    public function variables(array $vars): self
    {
        $this->variables = $vars;
        return $this;
    }

    /**
     * Получить текст SMS
     */
    public function getSmsText(): string
    {
        if ($this->message) {
            return $this->message;
        }

        // Рендерить из шаблона (если нужно)
        return view("sms/{$this->template}", array_merge($this->data, $this->variables))->render();
    }

    /**
     * Получить телефон получателя
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Получить данные для БД
     */
    public function toDatabase(): array
    {
        return array_merge(parent::toDatabase(), [
            'channel' => 'sms',
            'phone' => $this->phone,
            'message_preview' => substr($this->getSmsText(), 0, 100),
        ]);
    }

    /**
     * Для Twilio канала
     */
    public function toSms(): array
    {
        return [
            'to' => $this->phone,
            'message' => $this->getSmsText(),
            'priority' => $this->priority,
        ];
    }
}
