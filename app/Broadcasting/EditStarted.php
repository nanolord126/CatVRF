<?php

declare(strict_types=1);

namespace App\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;

final class EditStarted
{
    use SerializesModels;

    public function __construct(
        public int $userId,
        public int $tenantId,
        public string $documentType,
        public int $documentId,
        public string $userName,
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
            'event' => 'edit_started',
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'document_type' => $this->documentType,
            'document_id' => $this->documentId,
            'timestamp' => now()->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ];
    }
}
