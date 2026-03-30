<?php declare(strict_types=1);

namespace App\Events\Stream;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AnswerSent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public string $correlationId;

        public function __construct(
            public int $streamId,
            public string $fromPeerId,
            public string $toPeerId,
            public string $sdp,
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
                'type' => 'answer',
                'from' => $this->fromPeerId,
                'to' => $this->toPeerId,
                'sdp' => $this->sdp,
                'correlation_id' => $this->correlationId,
                'timestamp' => now()->toIso8601String(),
            ];
        }
}
