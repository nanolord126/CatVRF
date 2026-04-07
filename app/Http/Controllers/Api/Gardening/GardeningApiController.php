<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Gardening;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class GardeningApiController extends Controller
{

    private const RATE_LIMIT_SEC = 60; // Max 60 req/min for AI
        public function __construct(
            private readonly AIPlantGardenConstructor $aiConstructor,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Get Catalog with Bio-Climate Filtering.
         * Accessible by both B2C (standard) and B2B (wholesale) clients.
         */
        public function getCatalog(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                $this->logger->channel('audit')->info('Catalog requested', [
                    'cid' => $correlationId,
                    'ip' => $request->ip(),
                    'user_id' => $this->guard->id(),
                ]);
                $query = GardenProduct::query()
                    ->with(['plant', 'category', 'store'])
                    ->where('is_published', true);
                // 1. Climate Zone Filtering
                if ($request->has('zone')) {
                    $query->whereHas('plant', fn($q) => $q->where('hardiness_zone', $request->get('zone')));
                }
                // 2. Light Requirement Filtering
                if ($request->has('light')) {
                    $query->whereHas('plant', fn($q) => $q->where('light_requirement', $request->get('light')));
                }
                // 3. Category Filtering
                if ($request->has('category')) {
                    $query->where('category_id', $request->get('category'));
                }
                $products = $query->paginate(20);
                // Apply B2B Pricing if User is Professional Landscaper
                $products->getCollection()->transform(function ($product) use ($request) {
                    // Return b2b price ONLY if the request includes valid 'inn' or business context
                    $isB2B = $request->has('inn') && !empty($request->get('inn'));
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'is_b2b_price' => $isB2B,
                        'price' => $isB2B ? $product->price_b2b : $product->price_b2c,
                        'currency' => 'RUB',
                        'stock' => $product->stock_quantity,
                        'biological_data' => [
                            'zone' => $product->plant?->hardiness_zone,
                            'light' => $product->plant?->light_requirement,
                            'water' => $product->plant?->water_needs,
                        ],
                        'category' => $product->category?->name,
                        'store' => $product->store?->name,
                    ];
                });
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $products,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Catalog Fetch Failed', [
                    'cid' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'error' => 'Unable to fetch gardening catalog.',
                    'cid' => $correlationId
                ], 500);
            }
        }
        /**
         * AI Garden Consultant Endpoint.
         * Provides maintenance roadmaps and plant matches based on climate.
         */
        public function consultAI(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            // 1. Security & Fraud Check (Lute Mode)
            $this->fraudControl::check([
                'user_id' => $this->guard->id(),
                'action' => 'ai_consultation',
                'ip' => $request->ip(),
                'cid' => $correlationId
            ]);
            $request->validate([
                'hardiness_zone' => 'required|integer|between:1,11',
                'plot_description' => 'required|string|min:20',
                'interests' => 'array',
            ]);
            try {
                $dto = new GardenAIRequestDto(
                    userId: (int) $this->guard->id(),
                    hardinessZone: (int) $request->get('hardiness_zone'),
                    plotDescription: (string) $request->get('plot_description'),
                    preferences: (array) $request->get('interests', []),
                    correlationId: $correlationId
                );
                $result = $this->aiConstructor->generatePlan($dto);
                $this->logger->channel('audit')->info('AI Consultation Success', [
                    'cid' => $correlationId,
                    'user_id' => $this->guard->id(),
                    'zone' => $dto->hardinessZone
                ]);
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'ai_plan' => $result,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('AI Consultation FAILED', [
                    'cid' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'error' => 'AI Gardening Engine is temporarily busy.',
                    'cid' => $correlationId
                ], 502);
            }
        }
        /**
         * Submit Review for a Product/Experience.
         */
        public function submitReview(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $request->validate([
                'product_id' => 'required|exists:garden_products,id',
                'rating' => 'required|integer|between:1,5',
                'comment' => 'required|string|max:500',
            ]);
            try {
                $this->db->transaction(function() use ($request, $correlationId) {
                    \App\Domains\Gardening\Models\GardenReview::create([
                        'product_id' => $request->get('product_id'),
                        'user_id' => $this->guard->id(),
                        'rating' => $request->get('rating'),
                        'comment' => $request->get('comment'),
                        'correlation_id' => $correlationId,
                        'tenant_id' => filament()->getTenant()->id,
                    ]);
                });
                return $this->response->json([
                    'success' => true,
                    'message' => 'Review submitted for bio-botanical audit.',
                    'cid' => $correlationId
                ]);
            } catch (\Throwable $e) {
                return $this->response->json(['error' => 'Review creation failed.'], 500);
            }
        }
}
