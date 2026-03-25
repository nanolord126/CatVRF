<?php

declare(strict_types=1);

namespace App\Broadcasting;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class TeamPresenceChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $documentType,
        public readonly int $documentId,
        public readonly array $presentUsers,
        public readonly string $event, // joined, left, status_changed
        public readonly int $affectedUserId,
        public readonly string $correlationId
    ) {
        $this->log->channel('audit')->info('TeamPresenceChanged event broadcasted', [
            'tenant_id' => $this->tenantId,
            'document_type' => $this->documentType,
            'document_id' => $this->documentId,
            'presence_event' => $this->event,
            'affected_user_id' => $this->affectedUserId,
            'present_users_count' => count($this->presentUsers),
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     * Uses PresenceChannel for real-time user presence tracking.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("collab.{$this->tenantId}.{$this->documentType}.{$this->documentId}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'presence.changed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event' => 'presence_changed',
            'presence_event' => $this->event,
            'affected_user_id' => $this->affectedUserId,
            'present_users_count' => count($this->presentUsers),
            'present_users' => $this->presentUsers,
            'timestamp' => now()->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ];
    }

    /**
     * Determine if this event should be broadcast.
     */
    public function shouldBroadcast(): bool
    {
        return config('broadcasting.connections.pusher.enabled', true);
    }
}
