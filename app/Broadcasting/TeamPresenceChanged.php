<?php

declare(strict_types=1);

namespace App\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;

final class TeamPresenceChanged
{
    use SerializesModels;

    public function __construct(
        public int $tenantId,
        public string $documentType,
        public int $documentId,
        public array $presentUsers,
        public string $event, // joined, left, status_changed
        public int $affectedUserId,
        public string $correlationId
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel("collab.{$this->tenantId}.{$this->documentType}.{$this->documentId}");
    }

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
}
