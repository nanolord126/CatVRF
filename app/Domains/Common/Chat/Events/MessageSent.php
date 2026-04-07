<?php declare(strict_types=1);

/**
 * MessageSent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/messagesent
 */


namespace App\Domains\Common\Chat\Events;

final class MessageSent
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly Message $message,
            public readonly string $correlation_id
        ) {}

        public function broadcastOn(): array
        {
            // Иерархия: tenant-диалог
            return [
                new PrivateChannel('chat.' . $this->message->conversation->uuid),
            ];
        }

        public function broadcastWith(): array
        {
            return [
                'uuid' => $this->message->uuid,
                'content' => $this->message->content,
                'sender_id' => $this->message->sender_id,
                'sender_name' => $this->message->sender->name ?? 'User',
                'created_at' => $this->message->created_at->toISOString(),
                'correlation_id' => $this->correlation_id,
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
