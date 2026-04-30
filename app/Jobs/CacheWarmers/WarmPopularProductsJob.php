<?php

declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class WarmPopularProductsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 45;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly string $vertical,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('cache-warmer');
    }

    public function tags(): array
    {
        return ['cache-warmer', 'popular-products', $this->vertical];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addMinutes(15);
    }

    public function handle(): void
    {
        $this->logger->channel('audit')->$this->logger->info('[WarmPopularProductsJob] Started', [
            'vertical' => $this->vertical,
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $cacheKey = "popular_products:{$this->vertical}";
            $cacheTag = "popular_products_{$this->vertical}";

            $popularProducts = $this->getPopularProducts();

            $this->cache->store('redis')
                ->tags([$cacheTag])
                ->put($cacheKey, $popularProducts, CarbonImmutable::now()->addHours(4));

            $this->logger->channel('audit')->$this->logger->info('[WarmPopularProductsJob] Completed', [
                'vertical' => $this->vertical,
                'products_count' => count($popularProducts['products'] ?? []),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('[WarmPopularProductsJob] Failed', [
                'vertical' => $this->vertical,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function getPopularProducts(): array
    {
        $topProducts = $this->db->table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.vertical', $this->vertical)
            ->where('order_items.created_at', '>=', CarbonImmutable::now()->subDays(7))
            ->selectRaw('products.id, products.name, SUM(order_items.quantity) as total_sold')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(50)
            ->get()
            ->toArray();

        return [
            'vertical' => $this->vertical,
            'products' => $topProducts,
            'warmed_at' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ];
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('[WarmPopularProductsJob] Failed permanently', [
            'vertical' => $this->vertical,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
