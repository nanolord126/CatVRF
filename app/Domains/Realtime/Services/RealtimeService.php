<?php declare(strict_types=1);

namespace App\Domains\Realtime\Services;

use App\Domains\Realtime\Models\WebSocketConnection;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use App\Services\FraudControlService;
use App\Services\AuditService;

final readonly class RealtimeService
{
    public function __construct(
        private readonly BroadcastManager $broadcast,
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
    ) {}

    /**
     * Broadcast message to channel
     */
    public function broadcast(string $channel, string $event, array $data, string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->broadcast->channel($channel, function () {
            return true;
        });

        $this->broadcast->event(new \App\Domains\Realtime\Events\RealtimeMessage($channel, $event, $data));

        $this->logger->info('Realtime message broadcasted', [
            'channel' => $channel,
            'event' => $event,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Register WebSocket connection
     */
    public function registerConnection(int $userId, string $channel, string $connectionId, string $correlationId = ''): WebSocketConnection
    {
        $correlationId ??= Str::uuid()->toString();

        $this->fraud->check([
            'operation_type' => 'websocket_register',
            'correlation_id' => $correlationId,
        ]);

        $connection = WebSocketConnection::create([
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
            'user_id' => $userId,
            'channel' => $channel,
            'connection_id' => $connectionId,
            'is_active' => true,
        ]);

        $this->audit->record(
            action: 'websocket_registered',
            subjectType: WebSocketConnection::class,
            subjectId: $connection->id,
            newValues: ['channel' => $channel],
            correlationId: $correlationId,
        );

        $this->logger->info('WebSocket connection registered', [
            'connection_id' => $connectionId,
            'channel' => $channel,
            'correlation_id' => $correlationId,
        ]);

        return $connection;
    }

    /**
     * Disconnect WebSocket
     */
    public function disconnect(string $connectionId, string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        WebSocketConnection::where('connection_id', $connectionId)
            ->update(['is_active' => false]);

        $this->logger->info('WebSocket disconnected', [
            'connection_id' => $connectionId,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Get active connections for channel
     */
    public function getActiveConnections(string $channel): array
    {
        return WebSocketConnection::active()
            ->byChannel($channel)
            ->get()
            ->toArray();
    }

    /**
     * Get user's active connections
     */
    public function getUserConnections(int $userId): array
    {
        return WebSocketConnection::where('user_id', $userId)
            ->active()
            ->get()
            ->toArray();
    }

    /**
     * Disconnect all user connections
     */
    public function disconnectUser(int $userId, string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        WebSocketConnection::where('user_id', $userId)
            ->update(['is_active' => false]);

        $this->logger->info('User WebSocket connections disconnected', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);
    }
}
