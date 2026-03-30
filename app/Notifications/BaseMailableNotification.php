<?php declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Base class for email notifications
 *
 * Интеграция с Mailgun/SendGrid/SES через Laravel Mail
 */
abstract class BaseMailableNotification extends BaseNotification
{
    /**
     * Email template blade file (resources/views/emails/...)
     */
    protected string $template = 'emails.generic';

    /**
     * Email subject
     */
    protected string $subject = 'Notification';

    /**
     * Email from address (переопределять в подклассах)
     */
    protected ?string $fromAddress = null;

    /**
     * Email from name
     */
    protected ?string $fromName = null;

    /**
     * Reply-to address
     */
    protected ?string $replyTo = null;

    /**
     * CC addresses
     */
    protected array $cc = [];

    /**
     * BCC addresses
     */
    protected array $bcc = [];

    /**
     * Вложения (файлы или inline)
     */
    protected array $attachments = [];

    /**
     * Inline attachments (для логотипов, картинок)
     */
    protected array $inlineAttachments = [];

    /**
     * Locale для email
     */
    protected ?string $locale = null;

    /**
     * Конструктор
     */
    public function __construct(
        int $userId,
        int $tenantId,
        array $data = [],
        ?string $correlationId = null,
        array $channels = ['mail']
    ) {
        parent::__construct($userId, $tenantId, $data, $correlationId, $channels);
    }

    /**
     * Получить Envelope (тема, отправитель)
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                $this->fromAddress ?? config('mail.from.address'),
                $this->fromName ?? config('mail.from.name')
            ),
            replyTo: $this->replyTo ? [new Address($this->replyTo)] : [],
            subject: $this->subject,
        );
    }

    /**
     * Получить Content (template, вариант)
     */
    public function content(): Content
    {
        return new Content(
            view: $this->template,
            with: $this->getData(),
        );
    }

    /**
     * Получить HTML для email
     */
    public function toMail(): static
    {
        return $this;
    }

    /**
     * Установить email шаблон
     */
    public function template(string $view): self
    {
        $this->template = $view;
        return $this;
    }

    /**
     * Установить тему письма
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Установить отправителя
     */
    public function from(string $address, ?string $name = null): self
    {
        $this->fromAddress = $address;
        $this->fromName = $name;
        return $this;
    }

    /**
     * Установить reply-to
     */
    public function replyTo(string $address): self
    {
        $this->replyTo = $address;
        return $this;
    }

    /**
     * Добавить CC
     */
    public function cc(string ...$addresses): self
    {
        $this->cc = array_merge($this->cc, $addresses);
        return $this;
    }

    /**
     * Добавить BCC
     */
    public function bcc(string ...$addresses): self
    {
        $this->bcc = array_merge($this->bcc, $addresses);
        return $this;
    }

    /**
     * Добавить вложение
     */
    public function attach(string $path, array $options = []): self
    {
        $this->attachments[] = ['path' => $path, 'options' => $options];
        return $this;
    }

    /**
     * Добавить inline attachment (для картинок в теле письма)
     */
    public function attachInline(string $path, string $contentId): self
    {
        $this->inlineAttachments[] = ['path' => $path, 'contentId' => $contentId];
        return $this;
    }

    /**
     * Установить locale
     */
    public function locale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Получить данные для шаблона
     */
    public function getData(): array
    {
        return array_merge(parent::getData(), [
            'template' => $this->template,
            'subject' => $this->subject,
            'locale' => $this->locale ?? app()->getLocale(),
        ]);
    }

    /**
     * Получить вложения
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Получить inline attachments
     */
    public function getInlineAttachments(): array
    {
        return $this->inlineAttachments;
    }

    /**
     * Получить CC
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * Получить BCC
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }
}
