<?php declare(strict_types=1);

namespace App\Events\Stream;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

final class IceCandidateSent
{


        private string $correlationId;

        public function __construct(
            public int $streamId,
            public string $fromPeerId,
            public string $toPeerId,
            public string $candidate,
            public int $sdpMLineIndex,
            public ?string $sdpMid,
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
                'type' => 'ice-candidate',
                'from' => $this->fromPeerId,
                'to' => $this->toPeerId,
                'candidate' => $this->candidate,
                'sdpMLineIndex' => $this->sdpMLineIndex,
                'sdpMid' => $this->sdpMid,
                'correlation_id' => $this->correlationId,
                'timestamp' => now()->toIso8601String(),
            ];
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
