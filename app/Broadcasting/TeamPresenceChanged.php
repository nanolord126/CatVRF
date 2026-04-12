<?php declare(strict_types=1);

namespace App\Broadcasting;



use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class TeamPresenceChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
        private readonly int $tenantId,
        private readonly string $documentType,
        private readonly int $documentId,
        private readonly array $presentUsers,
        private readonly string $event,
        private readonly int $affectedUserId,
        private readonly string $correlationId,
    ) {
        $this->logger->info('TeamPresenceChanged event broadcasted', [
            'tenant_id' => $this->tenantId,
            'document_type' => $this->documentType,
            'document_id' => $this->documentId,
            'presence_event' => $this->event,
            'affected_user_id' => $this->affectedUserId,
            'present_users_count' => count($this->presentUsers),
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("collab.{$this->tenantId}.{$this->documentType}.{$this->documentId}");
    }

    public function broadcastAs(): string
    {
        return 'presence.changed';
    }

    /**
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

    public function broadcastWhen(): bool
    {
        return $this->config->get('broadcasting.connections.pusher.enabled', true);
    }
}
