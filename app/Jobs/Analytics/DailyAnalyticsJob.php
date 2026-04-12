<?php declare(strict_types=1);

namespace App\Jobs\Analytics;


use App\Services\DemandForecastService;
use App\Services\RecommendationService;
use Carbon\Carbon;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $correlationId;

    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('analytics');
    }

    public function tags(): array
    {
        return ['analytics', 'daily', 'forecast', 'recommendation'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(6);
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
                            Carbon::now(),
                            Carbon::now()->addDays(30)
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

                $this->logger->channel('audit')->info('Daily analytics job completed', [
                    'correlation_id' => $this->correlationId,
                    'timestamp' => Carbon::now()->toIso8601String(),
                ]);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
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
