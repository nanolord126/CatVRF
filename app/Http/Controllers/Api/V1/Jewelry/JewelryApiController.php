<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Jewelry;
use App\Http\Controllers\Controller;
use App\Domains\Luxury\Jewelry\Models\JewelryProduct;
use App\Domains\Luxury\Jewelry\Models\JewelryStore;
use App\Domains\Luxury\Jewelry\Models\JewelryCategory;
use App\Domains\Luxury\Jewelry\Services\AIJewelryConstructor;
use App\Domains\Luxury\Jewelry\DTOs\AIRecommendationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * JewelryApiController (Layer 6/9)
 * High-performance, secure API for consumer interactions in the Jewelry domain.
 */
class JewelryApiController extends Controller
{
    private string $correlationId;
    public function __construct(
        private readonly AIJewelryConstructor $aiConstructor
    ) {
        $this->correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());
    }
    /**
     * Get the multi-tenant jewelry catalog.
     */
    public function getCatalog(Request $request): JsonResponse
    {
        try {
            Log::channel('audit')->info('Jewelry catalog requested', [
                'cid' => $this->correlationId,
                'ip' => $request->ip(),
                'filters' => $request->only(['metal', 'stone', 'price_max']),
            ]);
            $query = JewelryProduct::query()
                ->where('is_published', true)
                ->with(['store', 'category', 'collection']);
            // Filter logic
            if ($request->has('metal')) {
                $query->where('metal_type', $request->get('metal'));
            }
            if ($request->has('stone')) {
                $query->whereJsonContains('gemstones', [['stone' => $request->get('stone')]]);
            }
            if ($request->has('price_max')) {
                $query->where('price_b2c', '<=', (int) $request->get('price_max'));
            }
            $products = $query->paginate(20);
            return response()->json([
                'success' => true,
                'cid' => $this->correlationId,
                'data' => $products,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Jewelry catalog failed', [
                'cid' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'cid' => $this->correlationId,
                'message' => 'Internal jewelry catalog error.',
            ], 500);
        }
    }
    /**
     * Get AI-driven product recommendations for the current user.
     */
    public function getAIRecommendations(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            // Create recommendation payload
            $dto = new AIRecommendationRequest(
                user_id: $user->id,
                occasion: $request->get('occasion', 'everyday'),
                preferred_metal: $request->get('metal'),
                budget_kopecks: (int) $request->get('budget_max', 10000000),
                style_preference: $request->get('style', 'luxury')
            );
            Log::channel('audit')->info('AI Jewelry recommendation requested', [
                'cid' => $this->correlationId,
                'user' => $user->id,
            ]);
            $result = $this->aiConstructor->recommendProducts($dto, $this->correlationId);
            return response()->json([
                'success' => true,
                'cid' => $this->correlationId,
                'match_score' => $result->confidence_score,
                'seasonal_type' => $result->payload['seasonal_type'] ?? 'unknown',
                'data' => $result->suggestions,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('AI Jewelry recommendation failed', [
                'cid' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'cid' => $this->correlationId,
                'message' => 'AI recommendation engine timeout or failure.',
            ], 500);
        }
    }
    /**
     * Obtain individual product details.
     */
    public function getProductDetails(string $sku): JsonResponse
    {
        try {
            $product = JewelryProduct::where('sku', $sku)
                ->where('is_published', true)
                ->with(['store', 'category', 'collection'])
                ->first();
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }
            return response()->json([
                'success' => true,
                'cid' => $this->correlationId,
                'data' => $product,
            ]);
        } catch (\Throwable $e) {
             return response()->json(['success' => false, 'cid' => $this->correlationId, 'message' => $e->getMessage()], 500);
        }
    }
    /**
     * Search categories for the jewelry vertical.
     */
    public function getCategories(): JsonResponse
    {
        $categories = JewelryCategory::all();
        return response()->json(['data' => $categories]);
    }
}
