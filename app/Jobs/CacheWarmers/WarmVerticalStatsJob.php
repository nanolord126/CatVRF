<?php declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WarmVerticalStatsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        private int $tries = 3;
        private int $timeout = 45;

        public function __construct(private readonly string $vertical) {}

        public function handle(): void
        {
            try {
                $cacheKey = "vertical_stats:{$this->vertical}";
                $cacheTag = "vertical_stats_{$this->vertical}";

                $stats = $this->calculateStats();

                Cache::store('redis')
                    ->tags([$cacheTag])
                    ->put($cacheKey, $stats, now()->addHours(8));

                Log::channel('audit')->info('Vertical stats cached', [
                    'vertical' => $this->vertical,
                    'correlation_id' => $stats['correlation_id'],
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to warm vertical stats cache', [
                    'vertical' => $this->vertical,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        private function calculateStats(): array
        {
            return [
                'vertical' => $this->vertical,
                'total_revenue' => 0,
                'orders_count' => 0,
                'users_count' => 0,
                'average_order' => 0,
                'calculated_at' => now()->toIso8601String(),
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            ];
        }
}
