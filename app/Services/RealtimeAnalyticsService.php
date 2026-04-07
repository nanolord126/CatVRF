<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Log\LogManager;

final readonly class RealtimeAnalyticsService
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}

    /**
         * Track event for analytics
         * @param int $tenantId
         * @param string $eventType
         * @param array $data
         * @return void
         */
        public function trackEvent(int $tenantId, string $eventType, array $data = []): void
        {
            $correlationId = Str::uuid()->toString();

            try {
                $now = now();
                $hour = $now->format('Y-m-d-H');
                $day = $now->format('Y-m-d');

                // Increment event counter
                $eventKey = "analytics:event:{$tenantId}:{$eventType}:{$hour}";
                cache()->increment($eventKey, 1, 3600 * 25); // Keep for 25 hours

                // Track revenue if applicable
                if (isset($data['amount'])) {
                    $revenueKey = "stats:revenue:hour:{$tenantId}:{$hour}";
                    cache()->increment($revenueKey, $data['amount'], 3600 * 25);

                    $revenueKeyDay = "stats:revenue:day:{$tenantId}:{$day}";
                    cache()->increment($revenueKeyDay, $data['amount'], 86400 * 30); // Keep for 30 days
                }

                $this->logger->channel('audit')->info('Analytics event tracked', [
                    'tenant_id' => $tenantId,
                    'event_type' => $eventType,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to track analytics', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        /**
         * Get real-time dashboard stats
         * @param int $tenantId
         * @return array
         */
        public function getDashboardStats(int $tenantId): array
        {
            $now = now();
            $today = $now->format('Y-m-d');
            $yesterday = $now->subDay()->format('Y-m-d');

            return [
                'today' => [
                    'orders' => (int) cache()->get("stats:orders:today:{$tenantId}", 0),
                    'revenue' => (int) cache()->get("stats:revenue:today:{$tenantId}", 0),
                    'pending' => (int) cache()->get("stats:orders:pending:{$tenantId}", 0),
                ],
                'yesterday' => [
                    'orders' => (int) cache()->get("stats:orders:yesterday:{$tenantId}", 0),
                    'revenue' => (int) cache()->get("stats:revenue:yesterday:{$tenantId}", 0),
                ],
                'trends' => $this->calculateTrends($tenantId),
            ];
        }

        /**
         * Calculate trends
         * @param int $tenantId
         * @return array
         */
        private function calculateTrends(int $tenantId): array
        {
            $today = (int) cache()->get("stats:revenue:today:{$tenantId}", 0);
            $yesterday = (int) cache()->get("stats:revenue:yesterday:{$tenantId}", 1);

            $revenueChange = $yesterday > 0 ? (($today - $yesterday) / $yesterday) * 100 : 0;

            return [
                'revenue_percent' => round($revenueChange, 1),
                'revenue_direction' => $revenueChange > 0 ? 'up' : 'down',
            ];
        }

        /**
         * Get hourly revenue chart data
         * @param int $tenantId
         * @param int $hours
         * @return array
         */
        public function getRevenueChartData(int $tenantId, int $hours = 24): array
        {
            $labels = [];
            $data = [];

            for ($i = $hours - 1; $i >= 0; $i--) {
                $hour = now()->subHours($i);
                $labels[] = $hour->format('H:i');
                $key = "stats:revenue:hour:{$tenantId}:{$hour->format('Y-m-d-H')}";
                $data[] = cache()->get($key, 0);
            }

            return compact('labels', 'data');
        }

        /**
         * Get top events
         * @param int $tenantId
         * @param int $limit
         * @return array
         */
        public function getTopEvents(int $tenantId, int $limit = 10): array
        {
            $hour = now()->format('Y-m-d-H');
            $events = [];

            // Get all event types and their counts
            $pattern = "analytics:event:{$tenantId}:*:{$hour}";
            // In production, use Redis SCAN for pattern matching

            return array_slice($events, 0, $limit);
        }

        /**
         * Aggregate hourly stats to daily
         * @param int $tenantId
         * @param string $date
         * @return void
         */
        public function aggregateDailyStats(int $tenantId, string $date): void
        {
            $correlationId = Str::uuid()->toString();

            try {
                $totalRevenue = 0;
                $carbon = Carbon::createFromFormat('Y-m-d', $date);

                // Sum hourly revenue for the day
                for ($h = 0; $h < 24; $h++) {
                    $hour = $carbon->copy()->addHours($h)->format('Y-m-d-H');
                    $revenue = (int) cache()->get("stats:revenue:hour:{$tenantId}:{$hour}", 0);
                    $totalRevenue += $revenue;
                }

                // Store daily aggregate
                cache()->put("stats:revenue:day:{$tenantId}:{$date}", $totalRevenue, 86400 * 365);

                $this->logger->channel('audit')->info('Daily stats aggregated', [
                    'tenant_id' => $tenantId,
                    'date' => $date,
                    'total_revenue' => $totalRevenue,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to aggregate daily stats', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }
}
