<?php declare(strict_types=1);

namespace App\Domains\Communication\Listeners;

use App\Domains\Communication\Events\MessageSentEvent;
use App\Services\AuditService;
use Illuminate\Log\LogManager;

final readonly class MessageSentAuditListener
{
    public function __construct(
        private AuditService $audit,
        private LogManager $logger,
    ) {}

    public function handle(MessageSentEvent $event): void
    {
        $message = $event->message;
        $correlationId = $event->correlationId;

        $this->audit->log('communication_message_sent', [
            'subject_type' => get_class($message),
            'subject_id' => $message->id,
            'new' => [
                'status' => $message->status,
                'channel_type' => $message->channel_type,
                'recipient_type' => $message->recipient_type,
            ],
        ], $correlationId);

        $this->logger->info('Communication message sent event handled', [
            'message_id' => $message->id,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Обработка неудачного выполнения listener'а.
     * Логируется в канал audit для мониторинга.
     */
    public function failed(object $event, \Throwable $exception): void
    {
        if (isset($this->logger)) {
            $this->logger->channel('audit')->error('Listener failed: ' . static::class, [
                'event' => get_class($event),
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'correlation_id' => $event->correlationId ?? null,
            ]);
        }
    }

    /**
     * Крайний срок для повторных попыток.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    /**
     * Очередь для обработки этого listener'а.
     */
    public function viaQueue(): string
    {
        return 'default';
    }
}
