<?php declare(strict_types=1);

namespace App\Domains\Common\Chat\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MessageSent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
}
