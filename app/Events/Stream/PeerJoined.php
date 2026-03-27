<?php

declare(strict_types=1);


namespace App\Events\Stream;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final /**
 * PeerJoined
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PeerJoined implements ShouldBroadcast
{
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
