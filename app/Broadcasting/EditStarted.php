<?php declare(strict_types=1);

namespace App\Broadcasting;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class EditStarted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $documentType,
        public readonly int $documentId,
        public readonly string $userName,
        public readonly string $correlationId,
    ) {
        Log::channel('audit')->info('EditStarted event broadcasted', [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'document_type' => $this->documentType,
            'document_id' => $this->documentId,
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("collab.{$this->tenantId}.{$this->documentType}.{$this->documentId}");
    }

    public function broadcastAs(): string
    {
        return 'edit.started';
    }

    /**
     * @return array<string, mixed>
     */
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

    public function broadcastWhen(): bool
    {
        return config('broadcasting.connections.pusher.enabled', true);
    }
}
