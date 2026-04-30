<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use Psr\Log\LoggerInterface;

use App\Services\DemandForecastService;
use App\Services\RecommendationService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class DailyAnalyticsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['analytics', 'daily', 'forecast', 'recommendation'];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(6);
    }

    public function handle(
        DemandForecastService $forecastService,
        RecommendationService $recommendationService
    ): void {
        try {
            $this->db->transaction(function () use ($forecastService, $recommendationService) {
                // Recalculate demand forecasts for all items
                $items = $this->db->table('products')
                    ->select('id', 'tenant_id')
                    ->where('is_active', true)
                    ->limit(1000)
                    ->get();

                foreach ($items as $item) {
                    try {
                        $forecast = $forecastService->forecastForItem(
                            $item->id,
                            new DateTime(),
                            (clone $now)->modify(+30 days)
                        );

                        $this->logger->channel('audit')->debug('Demand forecast calculated', [
                            'correlation_id' => $this->correlationId,
                            'item_id' => $item->id,
                            'predicted_demand' => $forecast->predicted_demand,
                        ]);
                    } catch (\Exception $e) {
                        $this->logger->channel('audit')->warning('Forecast calculation failed for item', [
                            'correlation_id' => $this->correlationId,
                            'item_id' => $item->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Recalculate recommendation embeddings
                $recommendationService->recalculateEmbeddings();

                $this->logger->channel('audit')->$this->logger->info('Daily analytics job completed', [
                    'correlation_id' => $this->correlationId,
                    'timestamp' => new DateTime()->toIso8601String(),
                ]);
            });
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('audit')->error('Daily analytics job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
