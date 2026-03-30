<?php declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WarmPopularProductsJob extends Model
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
                $cacheKey = "popular_products:{$this->vertical}";
                $cacheTag = "popular_products_{$this->vertical}";

                $popularProducts = $this->getPopularProducts();

                Cache::store('redis')
                    ->tags([$cacheTag])
                    ->put($cacheKey, $popularProducts, now()->addHours(4));

                Log::channel('audit')->info('Popular products cached', [
                    'vertical' => $this->vertical,
                    'products_count' => count($popularProducts),
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to warm popular products cache', [
                    'vertical' => $this->vertical,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        private function getPopularProducts(): array
        {
            return [
                'vertical' => $this->vertical,
                'products' => [],
                'warmed_at' => now()->toIso8601String(),
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            ];
        }
}
