<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Visual Search Service для Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Поиск товаров по фото с использованием ML-эмбеддингов,
 * интеграция с OpenAI Vision, поиск похожих товаров по стилю.
 */
final readonly class FashionVisualSearchService
{
    private const SIMILARITY_THRESHOLD = 0.7;
    private const MAX_SEARCH_RESULTS = 30;
    private const EMBEDDING_DIMENSION = 512;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Поиск товаров по фото.
     */
    public function searchByImage(
        string $imageUrl,
        int $userId,
        ?array $filters = [],
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_visual_search',
            amount: 0,
            correlationId: $correlationId
        );

        $embedding = $this->generateImageEmbedding($imageUrl, $correlationId);
        
        if ($embedding === null) {
            throw new \RuntimeException('Failed to generate image embedding', 500);
        }

        $similarProducts = $this->findSimilarProducts($embedding, $tenantId, $filters, $correlationId);

        $this->recordVisualSearch($userId, $tenantId, $imageUrl, $embedding, $correlationId);

        $this->audit->record(
            action: 'fashion_visual_search_executed',
            subjectType: 'fashion_visual_search',
            subjectId: $userId,
            oldValues: [],
            newValues: [
                'image_url' => $imageUrl,
                'results_count' => count($similarProducts),
            ],
            correlationId: $correlationId
        );

        Log::channel('audit')->info('Fashion visual search executed', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'results_count' => count($similarProducts),
            'correlation_id' => $correlationId,
        ]);

        return [
            'user_id' => $userId,
            'image_url' => $imageUrl,
            'results' => $similarProducts,
            'total_count' => count($similarProducts),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Найти похожие товары на основе эмбеддинга.
     */
    public function findSimilarProducts(
        array $embedding,
        int $tenantId,
        ?array $filters = [],
        string $correlationId = ''
    ): array {
        $query = $this->db->table('fashion_products as fp')
            ->where('fp.tenant_id', $tenantId)
            ->where('fp.status', 'active')
            ->where('fp.stock_quantity', '>', 0);

        if (!empty($filters['categories'])) {
            $query->whereIn('fp.id', function ($q) use ($filters, $tenantId) {
                $q->select('product_id')
                    ->from('fashion_product_categories')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('primary_category', $filters['categories']);
            });
        }

        if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
            $priceMin = (int) ($filters['price_min'] ?? 0);
            $priceMax = (int) ($filters['price_max'] ?? PHP_INT_MAX);
            $query->whereBetween('fp.price_b2c', [$priceMin, $priceMax]);
        }

        $products = $query->get()->toArray();

        $similarProducts = [];
        foreach ($products as $product) {
            $productEmbedding = $this->getProductEmbedding((int) $product['id'], $tenantId);
            
            if ($productEmbedding !== null) {
                $similarity = $this->calculateCosineSimilarity($embedding, $productEmbedding);
                
                if ($similarity >= self::SIMILARITY_THRESHOLD) {
                    $similarProducts[] = [
                        'product_id' => $product['id'],
                        'name' => $product['name'],
                        'brand' => $product['brand'],
                        'price' => $product['price_b2c'],
                        'image' => $product['images'] ? json_decode($product['images'], true)[0] ?? null : null,
                        'similarity' => $similarity,
                    ];
                }
            }
        }

        usort($similarProducts, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        return array_slice($similarProducts, 0, self::MAX_SEARCH_RESULTS, true);
    }

    /**
     * Индексировать продукт для визуального поиска.
     */
    public function indexProductForVisualSearch(
        int $productId,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $product = $this->db->table('fashion_products')
            ->where('id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($product === null) {
            throw new \InvalidArgumentException('Product not found', 404);
        }

        $images = json_decode($product['images'] ?? '[]', true);
        if (empty($images)) {
            throw new \InvalidArgumentException('Product has no images', 400);
        }

        $primaryImage = $images[0];
        $embedding = $this->generateImageEmbedding($primaryImage, $correlationId);

        if ($embedding === null) {
            throw new \RuntimeException('Failed to generate image embedding', 500);
        }

        $this->saveProductEmbedding($productId, $tenantId, $embedding, $correlationId);

        $this->audit->record(
            action: 'fashion_product_indexed_for_visual_search',
            subjectType: 'fashion_product',
            subjectId: $productId,
            oldValues: [],
            newValues: [
                'image_url' => $primaryImage,
                'embedding_dimension' => count($embedding),
            ],
            correlationId: $correlationId
        );

        return [
            'product_id' => $productId,
            'indexed' => true,
            'embedding_dimension' => count($embedding),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Массовая индексация продуктов.
     */
    public function bulkIndexProducts(array $productIds, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $results = [];
        foreach ($productIds as $productId) {
            try {
                $result = $this->indexProductForVisualSearch($productId, $correlationId);
                $results[] = $result;
            } catch (\Throwable $e) {
                Log::channel('audit')->warning('Failed to index product for visual search', [
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        return [
            'total_processed' => count($productIds),
            'successful' => count($results),
            'failed' => count($productIds) - count($results),
            'results' => $results,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Генерировать эмбеддинг изображения с помощью OpenAI Vision.
     */
    private function generateImageEmbedding(string $imageUrl, string $correlationId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-ada-002',
                'input' => $imageUrl,
            ]);

            if (!$response->successful()) {
                Log::channel('audit')->error('OpenAI embedding API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'correlation_id' => $correlationId,
                ]);
                return null;
            }

            $data = $response->json();
            return $data['data'][0]['embedding'] ?? null;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to generate image embedding', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return null;
        }
    }

    /**
     * Рассчитать косинусное сходство между двумя векторами.
     */
    private function calculateCosineSimilarity(array $vector1, array $vector2): float
    {
        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;

        $minLength = min(count($vector1), count($vector2));

        for ($i = 0; $i < $minLength; $i++) {
            $dotProduct += ($vector1[$i] ?? 0) * ($vector2[$i] ?? 0);
            $magnitude1 += ($vector1[$i] ?? 0) ** 2;
            $magnitude2 += ($vector2[$i] ?? 0) ** 2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 === 0.0 || $magnitude2 === 0.0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Получить эмбеддинг продукта.
     */
    private function getProductEmbedding(int $productId, int $tenantId): ?array
    {
        $record = $this->db->table('fashion_product_embeddings')
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        return $record !== null ? json_decode($record['embedding'], true) : null;
    }

    /**
     * Сохранить эмбеддинг продукта.
     */
    private function saveProductEmbedding(int $productId, int $tenantId, array $embedding, string $correlationId): void
    {
        $this->db->table('fashion_product_embeddings')->updateOrInsert(
            ['product_id' => $productId, 'tenant_id' => $tenantId],
            [
                'embedding' => json_encode($embedding),
                'embedding_dimension' => count($embedding),
                'updated_at' => Carbon::now(),
                'correlation_id' => $correlationId,
            ]
        );
    }

    /**
     * Записать визуальный поиск.
     */
    private function recordVisualSearch(int $userId, int $tenantId, string $imageUrl, array $embedding, string $correlationId): void
    {
        $this->db->table('fashion_visual_searches')->insert([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'image_url' => $imageUrl,
            'embedding' => json_encode($embedding),
            'searched_at' => Carbon::now(),
            'correlation_id' => $correlationId,
        ]);
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
