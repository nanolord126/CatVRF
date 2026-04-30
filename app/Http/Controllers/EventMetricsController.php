<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;

use App\Shared\Application\Services\EventMetricsCollector;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

/**
 * Controller for Prometheus Metrics endpoint
 */
final class EventMetricsController extends Controller
{
    public function __construct(private readonly CollectorRegistry $collectorRegistry,
        private readonly EventMetricsCollector $metricsCollector,
        private readonly ResponseFactory $response,
        private readonly DatabaseManager $db,) {
        // Update metrics before serving
        $this->metricsCollector->updateOutboxMetrics();
        $this->metricsCollector->updateDLQMetrics();
        $this->metricsCollector->updateQueueMetrics();
        $this->metricsCollector->updateClickHouseMetrics();
    }

    public function __invoke(): JsonResponse
    {
        $registry = $this->collectorRegistry /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
        $renderer = new RenderTextFormat();

        return $this->response->make(
            $renderer->render($registry->getMetricFamilySamples()),
            200,
            ['Content-Type' => RenderTextFormat::MIME_TYPE]
        );
    }

    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'healthy',
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'metrics' => [
                'outbox_pending' => $this->getOutboxPending(),
                'dlq_unprocessed' => $this->getDLQUnprocessed(),
                'queue_sizes' => $this->getQueueSizes(),
            ],
        ]);
    }

    private function getOutboxPending(): int
    {
        return $this->db->table('outbox_messages')
            ->where('status', 'pending')
            ->count();
    }

    private function getDLQUnprocessed(): int
    {
        return $this->db->table('dead_letter_queue')
            ->where('is_processed', false)
            ->count();
    }

    private function getQueueSizes(): array
    {
        $queues = ['emergency', 'payment', 'notification', 'default'];
        $sizes = [];

        foreach ($queues as $queue) {
            $sizes[$queue] = $this->db->table('jobs')
                ->where('queue', $queue)
                ->count();
        }

        return $sizes;
    }
}
