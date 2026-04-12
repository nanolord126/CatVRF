<?php

declare(strict_types=1);

namespace App\Domains\Communication\Events;

use App\Domains\Communication\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Real-time broadcast when a chat message is sent.
 * Broadcast via private channel: chat.{roomId}
 */
final class ChatMessageSentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use \Illuminate\Foundation\Events\Dispatchable, \Illuminate\Broadcasting\InteractsWithSockets, \Illuminate\Queue\SerializesModels;

    public function __construct(
        public readonly ChatMessage $chatMessage,
        public readonly string      $correlationId,
    ) {}

    /**
     * @return array<\Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chatMessage->room_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id'             => $this->chatMessage->id,
            'room_id'        => $this->chatMessage->room_id,
            'sender_id'      => $this->chatMessage->sender_id,
            'body'           => $this->chatMessage->body,
            'type'           => $this->chatMessage->type,
            'attachment_url' => $this->chatMessage->attachment_url,
            'created_at'     => $this->chatMessage->created_at?->toISOString(),
            'correlation_id' => $this->correlationId,
        ];
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

