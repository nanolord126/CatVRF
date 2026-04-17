<?php declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class HeatmapUpdateEvent
{


        /**
         * @var int Tenant ID for broadcast channel scoping
         */
        private int $tenantId;

        /**
         * @var string Heatmap type: 'geo' or 'click'
         */
        private string $heatmapType;

        /**
         * @var string|null Vertical filter (e.g., 'beauty', 'food')
         */
        public readonly ?string $vertical;

        /**
         * @var array Heatmap data payload
         *
         * For geo-heatmap:
         *   {
         *     points: [{lat, lng, value}, ...],
         *     stats: {point_count, max_value, avg_value, unique_cities}
         *   }
         *
         * For click-heatmap:
         *   {
         *     clicks: [{x, y, count, selector, browser, device}, ...],
         *     stats: {total_clicks, unique_users, avg_clicks_per_user, most_clicked}
         *   }
         */
        private array $data;

        /**
         * @var string Correlation ID for request tracing
         */
        private string $correlationId;

        /**
         * @var string|null User ID who triggered the update (optional)
         */
        public readonly ?string $userId;

        /**
         * Create a new event instance.
         *
         * @param int $tenantId Tenant ID for channel scoping
         * @param string $heatmapType 'geo' or 'click'
         * @param array $data Heatmap data payload
         * @param string|null $vertical Vertical filter name
         * @param string|null $userId User ID who triggered update (optional)
         * @param string|null $correlationId Custom correlation ID (auto-generated if null)
         *
         * @throws \InvalidArgumentException If heatmapType is invalid
         */
        public function __construct(
            int $tenantId,
            string $heatmapType,
            array $data,
            ?string $vertical = null,
            ?string $userId = null,
            ?string $correlationId = null, public readonly Request $request, public readonly LoggerInterface $logger
        ) {
            // Validate heatmap type
            if (!\in_array($heatmapType, ['geo', 'click'], true)) {
                throw new \InvalidArgumentException("Invalid heatmapType: {$heatmapType}. Must be 'geo' or 'click'.");
            }

            // Validate data structure
            if (empty($data) || !isset($data['points'], $data['stats']) && !isset($data['clicks'], $data['stats'])) {
                $this->logger->warning('HeatmapUpdateEvent: Invalid data structure', [
                    'heatmap_type' => $heatmapType,
                    'has_points' => isset($data['points']),
                    'has_clicks' => isset($data['clicks']),
                    'has_stats' => isset($data['stats']),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
            }

            $this->tenantId = $tenantId;
            $this->heatmapType = $heatmapType;
            $this->data = $data;
            $this->vertical = $vertical;
            $this->userId = $userId;
            $this->correlationId = $correlationId ?? "heatmap-{$this->generateTraceId()}";

            // Log event creation
            $this->logger->info('HeatmapUpdateEvent created', [
                'tenant_id' => $this->tenantId,
                'heatmap_type' => $this->heatmapType,
                'vertical' => $this->vertical,
                'user_id' => $this->userId,
                'correlation_id' => $this->correlationId,
                'data_point_count' => isset($data['points']) ? count($data['points']) : count($data['clicks'] ?? []),
            ]);
        }

        /**
         * Get the channels the event should broadcast on.
         *
         * Channel structure: private-tenant.{tenantId}.heatmap.{heatmapType}
         * This ensures only authenticated users of the tenant can receive updates
         *
         * @return array<int, PrivateChannel>
         */
        public function broadcastOn(): array
        {
            return [
                new PrivateChannel("tenant.{$this->tenantId}.heatmap.{$this->heatmapType}"),
            ];
        }

        /**
         * Get the data to broadcast.
         *
         * Formats event data for WebSocket transmission to clients.
         * Includes correlation ID for client-side request tracing.
         *
         * @return array Event payload for WebSocket
         */
        public function broadcastWith(): array
        {
            return [
                'type' => 'heatmap:updated',
                'heatmap_type' => $this->heatmapType,
                'tenant_id' => $this->tenantId,
                'vertical' => $this->vertical,
                'data' => $this->data,
                'correlation_id' => $this->correlationId,
                'user_id' => $this->userId,
                'timestamp' => \Carbon::now()->toIso8601String(),
            ];
        }

        /**
         * Get the broadcast event name.
         *
         * @return string Event name as received by WebSocket clients
         */
        public function broadcastAs(): string
        {
            return 'heatmap.updated';
        }

        /**
         * Generate a unique trace ID for this event.
         *
         * @return string Unique trace ID (timestamp-random)
         */
        private function generateTraceId(): string
        {
            return \Carbon::now()->timestamp . '-' . Str::random(8);
        }

        /**
         * Determine if the event should be broadcast.
         *
         * @return bool True to broadcast, false to suppress
         */
        public function shouldBroadcast(): bool
        {
            // Only broadcast if data is non-empty
            return !empty($this->data) && isset($this->data['stats']);
        }

        /**
         * Get the event name with correlation ID.
         *
         * For logging and tracing purposes.
         *
         * @return string Formatted event name with correlation ID
         */
        public function getTraceString(): string
        {
            return "{$this->heatmapType}_heatmap_update[{$this->correlationId}]";
        }
}
