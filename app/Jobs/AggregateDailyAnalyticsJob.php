<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AggregateDailyAnalyticsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $timeout = 300;

        public function __construct(
            public readonly int $tenantId,
        ) {
            $this->onQueue('analytics');
        }

        public function handle(RealtimeAnalyticsService $analyticsService): void
        {
            $yesterday = yesterday()->format('Y-m-d');

            try {
                $analyticsService->aggregateDailyStats($this->tenantId, $yesterday);

                Log::channel('audit')->info('Daily analytics aggregated', [
                    'tenant_id' => $this->tenantId,
                    'date' => $yesterday,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to aggregate daily analytics', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
