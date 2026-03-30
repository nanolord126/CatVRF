<?php declare(strict_types=1);

namespace App\Events\Stream;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PeerJoined extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public string $correlationId;

        public function __construct(
            public int $streamId,
            public string $peerId,
            public string $peerName = '',
        ) {
            $this->correlationId = Str::uuid()->toString();
        }

        public function broadcastOn(): array
        {
            return [
                new Channel("stream.{$this->streamId}"),
            ];
        }

        public function broadcastWith(): array
        {
            return [
                'type' => 'peer-joined',
                'peer_id' => $this->peerId,
                'peer_name' => $this->peerName,
                'correlation_id' => $this->correlationId,
                'timestamp' => now()->toIso8601String(),
            ];
        }
}
