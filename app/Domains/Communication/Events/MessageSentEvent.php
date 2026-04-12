<?php

declare(strict_types=1);

namespace App\Domains\Communication\Events;

use App\Domains\Communication\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a direct message is dispatched through a channel.
 * Broadcast via private channel: messages.{recipientId}
 */
final class MessageSentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use \Illuminate\Foundation\Events\Dispatchable, \Illuminate\Broadcasting\InteractsWithSockets, \Illuminate\Queue\SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly string  $correlationId,
    ) {}

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('messages.' . $this->message->recipient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message_id'     => $this->message->id,
            'channel_type'   => $this->message->channel_type,
            'body'           => $this->message->body,
            'subject'        => $this->message->subject,
            'sender_id'      => $this->message->sender_id,
            'status'         => $this->message->status,
            'sent_at'        => $this->message->sent_at?->toISOString(),
            'correlation_id' => $this->correlationId,
        ];
    }
}

