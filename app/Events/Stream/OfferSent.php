declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Events\Stream;

use App\Models\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final /**
 * OfferSent
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class OfferSent implements ShouldBroadcast
{
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
            'type' => 'offer',
            'from' => $this->fromPeerId,
            'to' => $this->toPeerId,
            'sdp' => $this->sdp,
            'correlation_id' => $this->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
