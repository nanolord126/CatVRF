<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Kids;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsProductController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly KidsInventoryService $inventory,
            private readonly AIKidsProductConstructor $aiConstructor,
            private readonly FraudControlService $fraud,
        ) {}
        /**
         * Get list of children products with tenant scoping.
         * GET /api/v1/kids/products
         */
        public function index(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $products = KidsProduct::available()
                ->with(['toy', 'clothing', 'store'])
                ->latest()
                ->paginate($request->get('limit', 20));
            return response()->json([
                'success' => true,
                'data' => $products,
                'correlation_id' => $correlationId,
            ]);
        }
        /**
         * Get specific product details.
         * GET /api/v1/kids/products/{id}
         */
        public function show(string $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $product = KidsProduct::with(['toy', 'clothing', 'store', 'reviews'])
                ->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $product,
                'correlation_id' => $correlationId,
            ]);
        }
        /**
         * AI-Driven product recommendation search.
         * POST /api/v1/kids/products/ai-suggest
         */
        public function aiSuggest(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $request->validate([
                'age_months' => 'required|integer|min:0|max:216',
                'interests' => 'nullable|array',
                'budget_max' => 'nullable|integer',
            ]);
            try {
                $suggestions = $this->aiConstructor->suggestProductByAge(
                    ageMonths: (int) $request->get('age_months'),
                    interests: $request->get('interests', []),
                    correlationId: $correlationId
                );
                return response()->json([
                    'success' => true,
                    'data' => $suggestions,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('AI Suggestion API Failure', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'AI recommendation engine is temporarily unavailable.',
                    'correlation_id' => $correlationId,
                ], 503);
            }
        }
        /**
         * Book/Reserve product stock.
         * POST /api/v1/kids/products/{id}/reserve
         */
        public function reserve(Request $request, string $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);
            $this->fraud->check('kids_reservation', $request->ip());
            $result = $this->inventory->reserveProduct(
                productId: (int) $id,
                quantity: (int) $request->get('quantity'),
                correlationId: $correlationId
            );
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock for this product.',
                    'correlation_id' => $correlationId,
                ], 400);
            }
            return response()->json([
                'success' => true,
                'message' => 'Stock successfully reserved.',
                'correlation_id' => $correlationId,
            ]);
        }
}
