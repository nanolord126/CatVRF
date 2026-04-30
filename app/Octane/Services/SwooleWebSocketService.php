<?php

declare(strict_types=1);

namespace App\Octane\Services;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Swoole\WebSocket\Server as WebSocketServer;
use Swoole\WebSocket\Frame;

/**
 * Swoole WebSocket Service for real-time communications
 *
 * Handles WebSocket connections for:
 * - Video consultation rooms
 * - Real-time notifications
 * - Check-in events
 * - Live updates
 */
final class SwooleWebSocketService
{
    private ?WebSocketServer $server;

    private array $rooms = [];

    public function __construct(private readonly LoggerInterface $logger,
        private readonly SwooleTableService $tableService,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,) {}

    /**
     * Initialize WebSocket server
     */
    public function initialize(string $host = '0.0.0.0', int $port = 9501): void
    {
        $this->server = new WebSocketServer($host, $port);

        $this->server->set([
            'worker_num' => 4,
            'task_worker_num' => 2,
            'enable_coroutine' => true,
        ]);

        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('workerStart', [$this, 'onWorkerStart']);

        $this->log->$this->logger->info('Swoole WebSocket server initialized', [
            'host' => $host,
            'port' => $port,
        ]);
    }

    /**
     * Start WebSocket server
     */
    public function start(): void
    {
        if ($this->server === null) {
            throw new \RuntimeException('WebSocket server not initialized');
        }

        $this->server->start();
    }

    /**
     * Handle new WebSocket connection
     */
    public function onOpen(WebSocketServer $server, $request): void
    {
        $fd = $request->fd;
        $path = $request->server['path_info'] ?? '/';

        $this->log->$this->logger->info('WebSocket connection opened', [
            'fd' => $fd,
            'path' => $path,
            'ip' => $request->server['remote_addr'] ?? 'unknown',
        ]);

        // Parse connection parameters
        $queryParams = $this->parseQueryParams($request->server['query_string'] ?? '');
        $room = $queryParams['room'] ?? null;
        $userId = $queryParams['user_id'] ?? null;
        $token = $queryParams['token'] ?? null;

        // Validate token
        if (! $this->validateToken($token, $userId)) {
            $server->push($fd, json_encode([
                'type' => 'error',
                'message' => 'Invalid token',
            ]));
            $server->close($fd);

            return;
        }

        // Join room if specified
        if ($room !== null) {
            $this->joinRoom($fd, $room, (int) $userId);
        }

        // Store connection in Swoole table
        $videoRooms = $this->tableService->videoRooms();
        if ($videoRooms && $room !== null) {
            $videoRooms->set("connection:$fd", [
                'fd' => $fd,
                'room' => $room,
                'user_id' => (int) $userId,
                'connected_at' => time(),
                'status' => 'active',
            ]);
        }

        // Send welcome message
        $server->push($fd, json_encode([
            'type' => 'connected',
            'fd' => $fd,
            'room' => $room,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]));
    }

    /**
     * Handle incoming WebSocket message
     */
    public function onMessage(WebSocketServer $server, Frame $frame): void
    {
        $fd = $frame->fd;
        $data = json_decode($frame->data, true);

        if ($data === null) {
            $this->log->warning('Invalid JSON received', ['fd' => $fd]);

            return;
        }

        $type = $data['type'] ?? 'unknown';

        $this->log->$this->logger->info('WebSocket message received', [
            'fd' => $fd,
            'type' => $type,
        ]);

        switch ($type) {
            case 'join_room':
                $this->handleJoinRoom($server, $fd, $data);
                break;

            case 'leave_room':
                $this->handleLeaveRoom($server, $fd, $data);
                break;

            case 'broadcast':
                $this->handleBroadcast($server, $fd, $data);
                break;

            case 'ping':
                $server->push($fd, json_encode(['type' => 'pong']));
                break;

            default:
                $this->log->warning('Unknown message type', ['type' => $type]);
        }
    }

    /**
     * Handle WebSocket connection close
     */
    public function onClose(WebSocketServer $server, $fd): void
    {
        $this->log->$this->logger->info('WebSocket connection closed', ['fd' => $fd]);

        // Remove from room
        $this->leaveRoom($fd);

        // Remove from Swoole table
        $videoRooms = $this->tableService->videoRooms();
        if ($videoRooms) {
            $videoRooms->del("connection:$fd");
        }
    }

    /**
     * Handle worker start
     */
    public function onWorkerStart(WebSocketServer $server, $workerId): void
    {
        $this->log->$this->logger->info('WebSocket worker started', ['worker_id' => $workerId]);
    }

    /**
     * Broadcast message to all participants in a room
     */
    public function broadcastToRoom(WebSocketServer $server, string $room, array $message): void
    {
        if (! isset($this->rooms[$room])) {
            return;
        }

        $jsonMessage = json_encode($message);
        foreach ($this->rooms[$room] as $fd => $participant) {
            if ($server->exist($fd)) {
                $server->push($fd, $jsonMessage);
            }
        }

        $this->log->$this->logger->info('Broadcast to room', [
            'room' => $room,
            'recipient_count' => count($this->rooms[$room]),
        ]);
    }

    /**
     * Get room statistics
     */
    public function getRoomStats(): array
    {
        $stats = [];
        foreach ($this->rooms as $room => $participants) {
            $stats[$room] = [
                'participant_count' => count($participants),
                'participants' => array_values($participants),
            ];
        }

        return $stats;
    }

    /**
     * Generate WebSocket token for user
     */
    public function generateToken(int $userId, int $ttl = 3600): string
    {
        $token = hash('sha256', $userId.time().random_bytes(16));
        $key = "ws_token:{$userId}:{$token}";

        $this->redis->setex($key, $ttl, '1');

        return $token;
    }

    /**
     * Create video consultation room
     */
    public function createVideoRoom(string $roomId, int $doctorId, int $patientId, int $ttl = 3600): void
    {
        $videoRooms = $this->tableService->videoRooms();
        if ($videoRooms) {
            $videoRooms->set($roomId, [
                'room_id' => $roomId,
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'status' => 'active',
                'started_at' => time(),
                'expires_at' => time() + $ttl,
                'participant_count' => 0,
            ]);
        }

        // Also store in Redis for persistence
        $this->redis->setex("video_room:$roomId", $ttl, json_encode([
            'room_id' => $roomId,
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'status' => 'active',
            'started_at' => time(),
            'expires_at' => time() + $ttl,
        ]));

        $this->log->$this->logger->info('Video room created', [
            'room_id' => $roomId,
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'ttl' => $ttl,
        ]);
    }

    /**
     * Close video consultation room
     */
    public function closeVideoRoom(string $roomId): void
    {
        $videoRooms = $this->tableService->videoRooms();
        if ($videoRooms) {
            $videoRooms->del($roomId);
        }

        $this->redis->del("video_room:$roomId");

        // Disconnect all participants
        if (isset($this->rooms[$roomId])) {
            foreach ($this->rooms[$roomId] as $fd => $participant) {
                if ($this->server && $this->server->exist($fd)) {
                    $this->server->push($fd, json_encode([
                        'type' => 'room_closed',
                        'room' => $roomId,
                    ]));
                    $this->server->close($fd);
                }
            }
            unset($this->rooms[$roomId]);
        }

        $this->log->$this->logger->info('Video room closed', ['room_id' => $roomId]);
    }

    /**
     * Join a room
     */
    private function joinRoom(int $fd, string $room, int $userId): void
    {
        if (! isset($this->rooms[$room])) {
            $this->rooms[$room] = [];
        }

        $this->rooms[$room][$fd] = [
            'user_id' => $userId,
            'joined_at' => time(),
        ];

        // Update room participant count in Swoole table
        $videoRooms = $this->tableService->videoRooms();
        if ($videoRooms) {
            $roomData = $videoRooms->get($room);
            if ($roomData) {
                $videoRooms->set($room, [
                    'room_id' => $roomData['room_id'],
                    'doctor_id' => $roomData['doctor_id'],
                    'patient_id' => $roomData['patient_id'],
                    'status' => $roomData['status'],
                    'started_at' => $roomData['started_at'],
                    'expires_at' => $roomData['expires_at'],
                    'participant_count' => count($this->rooms[$room]),
                ]);
            }
        }

        $this->log->$this->logger->info('User joined room', [
            'fd' => $fd,
            'room' => $room,
            'user_id' => $userId,
            'participant_count' => count($this->rooms[$room]),
        ]);
    }

    /**
     * Leave a room
     */
    private function leaveRoom(int $fd): void
    {
        foreach ($this->rooms as $room => $participants) {
            if (isset($participants[$fd])) {
                unset($this->rooms[$room][$fd]);

                // Update room participant count
                $videoRooms = $this->tableService->videoRooms();
                if ($videoRooms) {
                    $roomData = $videoRooms->get($room);
                    if ($roomData) {
                        $videoRooms->set($room, [
                            'room_id' => $roomData['room_id'],
                            'doctor_id' => $roomData['doctor_id'],
                            'patient_id' => $roomData['patient_id'],
                            'status' => $roomData['status'],
                            'started_at' => $roomData['started_at'],
                            'expires_at' => $roomData['expires_at'],
                            'participant_count' => count($this->rooms[$room]),
                        ]);
                    }
                }

                $this->log->$this->logger->info('User left room', [
                    'fd' => $fd,
                    'room' => $room,
                    'participant_count' => count($this->rooms[$room]),
                ]);

                // Clean up empty rooms
                if (empty($this->rooms[$room])) {
                    unset($this->rooms[$room]);
                    $this->log->$this->logger->info('Room removed (empty)', ['room' => $room]);
                }
            }
        }
    }

    /**
     * Handle join room message
     */
    private function handleJoinRoom(WebSocketServer $server, int $fd, array $data): void
    {
        $room = $data['room'] ?? null;
        $userId = $data['user_id'] ?? null;

        if ($room === null || $userId === null) {
            $server->push($fd, json_encode([
                'type' => 'error',
                'message' => 'Missing room or user_id',
            ]));

            return;
        }

        $this->joinRoom($fd, $room, (int) $userId);

        $server->push($fd, json_encode([
            'type' => 'room_joined',
            'room' => $room,
            'participant_count' => count($this->rooms[$room] ?? []),
        ]));
    }

    /**
     * Handle leave room message
     */
    private function handleLeaveRoom(WebSocketServer $server, int $fd, array $data): void
    {
        $this->leaveRoom($fd);

        $server->push($fd, json_encode([
            'type' => 'room_left',
        ]));
    }

    /**
     * Handle broadcast message
     */
    private function handleBroadcast(WebSocketServer $server, int $fd, array $data): void
    {
        $room = $data['room'] ?? null;
        $message = $data['message'] ?? null;

        if ($room === null || $message === null) {
            return;
        }

        $this->broadcastToRoom($server, $room, [
            'type' => 'broadcast',
            'room' => $room,
            'message' => $message,
            'sender_fd' => $fd,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }

    /**
     * Parse query parameters from connection string
     */
    private function parseQueryParams(string $queryString): array
    {
        $params = [];
        parse_str($queryString, $params);

        return $params;
    }

    /**
     * Validate WebSocket token
     */
    private function validateToken(?string $token, ?string $userId): bool
    {
        if ($token === null || $userId === null) {
            return false;
        }

        // Validate token with Redis or cache
        $key = "ws_token:{$userId}:{$token}";
        $valid = $this->redis->exists($key);

        return $valid > 0;
    }
}
