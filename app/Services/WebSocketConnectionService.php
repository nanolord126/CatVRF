<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service: WebSocket Connection Management
 * 
 * Функции:
 * - Connection pooling
 * - Heartbeat monitoring
 * - Automatic reconnection
 * - Connection metrics
 * 
 * @package App\Services
 */
final class WebSocketConnectionService
{
    /**
     * Register new connection
     * @param string $connectionId
     * @param int $userId
     * @param int $tenantId
     * @return bool
     */
    public function registerConnection(string $connectionId, int $userId, int $tenantId): bool
    {
        try {
            $key = "ws:connection:{$connectionId}";
            
            cache()->put($key, [
                'connection_id' => $connectionId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'connected_at' => now()->toIso8601String(),
                'last_heartbeat' => now()->toIso8601String(),
                'subscriptions' => [],
            ], 3600); // Keep for 1 hour

            // Track connection count
            $countKey = "ws:connections:count:{$tenantId}";
            cache()->increment($countKey, 1, 3600);

            $this->log->channel('audit')->info('WebSocket connection registered', [
                'connection_id' => $connectionId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to register connection', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Unregister connection
     * @param string $connectionId
     * @param int $tenantId
     * @return bool
     */
    public function unregisterConnection(string $connectionId, int $tenantId): bool
    {
        try {
            $key = "ws:connection:{$connectionId}";
            cache()->forget($key);

            // Decrement connection count
            $countKey = "ws:connections:count:{$tenantId}";
            cache()->decrement($countKey, 1);

            $this->log->channel('audit')->info('WebSocket connection unregistered', [
                'connection_id' => $connectionId,
                'tenant_id' => $tenantId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to unregister connection', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Update heartbeat
     * @param string $connectionId
     * @return bool
     */
    public function heartbeat(string $connectionId): bool
    {
        try {
            $key = "ws:connection:{$connectionId}";
            $connection = cache()->get($key);

            if ($connection) {
                $connection['last_heartbeat'] = now()->toIso8601String();
                cache()->put($key, $connection, 3600);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Heartbeat failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get connection count
     * @param int $tenantId
     * @return int
     */
    public function getConnectionCount(int $tenantId): int
    {
        $key = "ws:connections:count:{$tenantId}";
        return (int) cache()->get($key, 0);
    }

    /**
     * Add subscription to connection
     * @param string $connectionId
     * @param string $channel
     * @return bool
     */
    public function addSubscription(string $connectionId, string $channel): bool
    {
        try {
            $key = "ws:connection:{$connectionId}";
            $connection = cache()->get($key);

            if ($connection) {
                if (!in_array($channel, $connection['subscriptions'])) {
                    $connection['subscriptions'][] = $channel;
                    cache()->put($key, $connection, 3600);
                }
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to add subscription', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove subscription from connection
     * @param string $connectionId
     * @param string $channel
     * @return bool
     */
    public function removeSubscription(string $connectionId, string $channel): bool
    {
        try {
            $key = "ws:connection:{$connectionId}";
            $connection = cache()->get($key);

            if ($connection) {
                $connection['subscriptions'] = array_filter(
                    $connection['subscriptions'],
                    fn($ch) => $ch !== $channel
                );
                cache()->put($key, $connection, 3600);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to remove subscription', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get connection metrics
     * @param int $tenantId
     * @return array
     */
    public function getMetrics(int $tenantId): array
    {
        return [
            'active_connections' => $this->getConnectionCount($tenantId),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
