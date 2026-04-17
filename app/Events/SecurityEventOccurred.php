<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Security event для real-time Filament SecurityDashboard.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Broadcast каналы:
 *   - Presence channel security.tenant.{tenantId} (Admin + Tenant Panel)
 *   - Private channel security.admin (только для Admin Panel)
 */
final class SecurityEventOccurred implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly string $eventType,
        private readonly int    $userId,
        private readonly string $severity,
        private readonly string $correlationId,
        private readonly int    $tenantId,
        private string $occurredAt = '',
    ) {}

    public static function now(
        string $eventType,
        int    $userId,
        string $severity,
        string $correlationId,
        int    $tenantId,
    ): self {
        return new self(
            eventType:     $eventType,
            userId:        $userId,
            severity:      $severity,
            correlationId: $correlationId,
            tenantId:      $tenantId,
            occurredAt:    \Illuminate\Support\Carbon::now()->toIso8601String(),
        );
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("security.tenant.{$this->tenantId}"),
            new Channel('security.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SecurityEventOccurred';
    }

    public function broadcastWith(): array
    {
        return [
            'event_type'     => $this->eventType,
            'user_id'        => $this->userId,
            'severity'       => $this->severity,
            'correlation_id' => $this->correlationId,
            'tenant_id'      => $this->tenantId,
            'occurred_at'    => $this->occurredAt,
        ];
    }
}
