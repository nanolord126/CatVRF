<?php declare(strict_types=1);

namespace App\Modules\Marketplace\Stationery\Controllers;




use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use App\Http\Controllers\Controller;

final class StationeryMarketplaceController extends Controller
{

    /**
         * StationeryMarketplaceController constructor.
         */
        public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly ResponseFactory $responseFactory,
            private readonly StationeryService $stationery,
            private readonly AIStationeryConstructor $ai,
            private readonly FraudControlService $fraud,
        ) {}

        /**
         * Catalog Browser with B2B vs B2C Pricing Engine.
         * (>60 lines per CANON 2026).
         */
        public function index(Request $request): JsonResponse
        {
            $correlation_id = $request->header('X-Correlation-ID', (string) Str::uuid());

            try {
                // 1. Audit Security Check
                $this->fraud->check();
                $this->logger->info('Stationery catalog accessed', [
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $correlation_id,
                    'ip' => $request->ip(),
                ]);

                // 2. Resolve Business Mode (B2B vs B2C)
                $isB2B = $request->has('inn') && $request->has('business_card_id');
                $businessGroupId = $request->get('business_group_id');

                // 3. Build Catalog Query
                $query = StationeryProduct::query()
                    ->where('is_active', true)
                    ->with(['category', 'store']);

                // Filter by Store
                if ($request->has('store_id')) {
                    $query->where('store_id', $request->get('store_id'));
                }

                // Filter by Category
                if ($request->has('category_id')) {
                    $query->where('category_id', $request->get('category_id'));
                }

                // Search
                if ($search = $request->get('q')) {
                    $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%"));
                }

                // 4. Transform Prices based on Mode
                $products = $query->paginate($request->get('per_page', 20))
                    ->through(function ($p) use ($isB2B, $businessGroupId) {
                        $finalPrice = $p->price_cents;
                        $mode = 'B2C';

                        if ($isB2B) {
                            $finalPrice = $p->b2b_price_cents ?? $p->price_cents;
                            $mode = 'B2B';
                        }

                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'sku' => $p->sku,
                            'price' => $finalPrice / 100,
                            'currency' => 'RUB',
                            'mode' => $mode,
                            'stock' => $p->stock_quantity,
                            'store' => $p->store->name,
                            'category' => $p->category->name,
                            'is_gift_ready' => $p->has_gift_wrapping,
                        ];
                    });

                return $this->responseFactory->json([
                    'success' => true,
                    'correlation_id' => $correlation_id,
                    'data' => $products,
                    'meta' => [
                        'mode' => $isB2B ? 'B2B (Wholesale)' : 'B2C (Retail)',
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('Catalog loading failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlation_id,
                ]);

                return $this->responseFactory->json([
                    'success' => false,
                    'message' => 'Internal Catalog Error',
                    'correlation_id' => $correlation_id,
                ], 500);
            }
        }

        /**
         * AI-Driven Kit Builder for Office or School.
         */
        public function constructKit(Request $request): JsonResponse
        {
            $correlation_id = (string) Str::uuid();

            $request->validate([
                'theme' => 'required|string|in:school,office,premium,minimal,calligraphy',
                'budget_cents' => 'required|integer|min:1000',
            ]);

            try {
                $this->db->beginTransaction();

                // AI Logic from AIStationeryConstructor
                $kitItems = $this->ai->constructRecommendedKit(
                    $request->get('theme'),
                    (int) $request->get('budget_cents')
                );

                $this->db->commit();

                return $this->responseFactory->json([
                    'success' => true,
                    'theme' => $request->get('theme'),
                    'total_items' => count($kitItems),
                    'items' => $kitItems,
                    'correlation_id' => $correlation_id,
                ]);

            } catch (\Throwable $e) {
                $this->db->rollBack();
                $this->logger->error('AI Kit Construction failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlation_id,
                ]);

                return $this->responseFactory->json([
                    'success' => false,
                    'message' => 'AI Service Unavailable',
                    'correlation_id' => $correlation_id,
                ], 503);
            }
        }
}
