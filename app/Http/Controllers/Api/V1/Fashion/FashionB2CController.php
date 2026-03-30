<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Fashion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionB2CController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FashionService $fashionService,
            private readonly AIOutfitConstructor $aiConstructor,
            private readonly RateLimiterService $rateLimiter
        ) {}
        /**
         * @OA\Get(
         *     path="/api/v1/fashion/products",
         *     summary="Получение каталога товаров (B2C)",
         *     tags={"Fashion"},
         *     @OA\Parameter(name="store_id", in="query", required=false, @OA\Schema(type="integer")),
         *     @OA\Response(response=200, description="Catalog fetched successfully")
         * )
         */
        public function index(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                $this->rateLimiter->check($request->ip(), 'fashion_browse');
                $products = FashionProduct::query()
                    ->where('is_active', true)
                    ->when($request->get('store_id'), fn ($q, $id) => $q->where('store_id', $id))
                    ->paginate(20);
                Log::channel('audit')->info('B2C products browsed', [
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                    'count' => $products->count(),
                ]);
                return response()->json([
                    'success' => true,
                    'data' => $products,
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('B2C index error', ['trace' => $e->getTraceAsString(), 'correlation_id' => $correlationId]);
                return response()->json(['error' => 'Internal Server Error', 'correlation_id' => $correlationId], 500);
            }
        }
        /**
         * @OA\Post(
         *     path="/api/v1/fashion/reserve",
         *     summary="Резервирование товара на 20 минут",
         *     tags={"Fashion"},
         *     @OA\RequestBody(required=true, @OA\JsonContent(
         *         @OA\Property(property="product_id", type="integer"),
         *         @OA\Property(property="quantity", type="integer", default=1)
         *     )),
         *     @OA\Response(response=200, description="Reserved successfully")
         * )
         */
        public function reserve(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $request->validate([
                'product_id' => 'required|exists:fashion_products,id',
                'quantity' => 'integer|min:1',
            ]);
            try {
                FraudControlService::check(['action' => 'reserve_stock', 'user_id' => $request->user()?->id]);
                $success = $this->fashionService->reserveItem(
                    (int) $request->get('product_id'),
                    (int) $request->get('quantity', 1),
                    $correlationId
                );
                if (!$success) {
                    return response()->json(['error' => 'Insufficient stock', 'correlation_id' => $correlationId], 422);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Product reserved for 20 minutes',
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Reservation failed', ['msg' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['error' => 'Reservation failed', 'correlation_id' => $correlationId], 400);
            }
        }
        /**
         * @OA\Post(
         *     path="/api/v1/fashion/ai-outfit",
         *     summary="Генерация AI-аутфита по фото",
         *     tags={"Fashion"},
         *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data")),
         *     @OA\Response(response=200, description="Outfit generated")
         * )
         */
        public function aiOutfit(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            if (!$request->hasFile('photo')) {
                return response()->json(['error' => 'Photo required', 'correlation_id' => $correlationId], 400);
            }
            try {
                $outfit = $this->aiConstructor->generateFromPhoto(
                    $request->file('photo'),
                    ['user_id' => $request->user()?->id]
                );
                return response()->json([
                    'success' => true,
                    'data' => $outfit,
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                return response()->json(['error' => 'AI processing failed', 'correlation_id' => $correlationId], 500);
            }
        }
}
