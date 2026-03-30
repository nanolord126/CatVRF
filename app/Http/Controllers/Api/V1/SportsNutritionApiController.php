<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SportsNutritionApiController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly AISupplementConstructor $aiConstructor
        ) {}
        /**
         * GET /api/v1/sports-nutrition/catalog
         * Full catalog with multi-tenant filtering and advanced query logic.
         */
        public function index(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
            try {
                $query = SportsNutritionProduct::query()
                    ->where('is_published', true)
                    ->where('stock_quantity', '>', 0)
                    ->with(['store', 'category'])
                    ->orderBy('created_at', 'desc');
                // Filtering by category (B2C catalog)
                if ($request->has('category_id')) {
                    $query->where('category_id', $request->get('category_id'));
                }
                // Vegan Filter (User preference)
                if ($request->boolean('vegan')) {
                    $query->where('is_vegan', true);
                }
                // Exclude Allergens
                if ($request->has('exclude_allergens')) {
                    $exclude = (array)$request->get('exclude_allergens');
                    $query->where(function ($q) use ($exclude) {
                        foreach ($exclude as $allergen) {
                            $q->whereJsonDoesntContain('allergens', $allergen);
                        }
                    });
                }
                $results = $query->paginate($request->get('per_page', 20));
                Log::channel('audit')->info('Catalog GET success', [
                    'cid' => $correlationId,
                    'user' => $request->user()?->id,
                    'count' => $results->count()
                ]);
                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $results->items(),
                    'meta' => [
                        'total' => $results->total(),
                        'page' => $results->currentPage()
                    ]
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Catalog GET failed', [
                    'cid' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => 'Internal Catalog Error', 'cid' => $correlationId], 500);
            }
        }
        /**
         * POST /api/v1/sports-nutrition/ai-recommend
         * Specialized LLM-based stack generation based on biometrics & goals.
         */
        public function recommend(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
            $request->validate([
                'weight_kg' => 'required|numeric|min:40|max:250',
                'age' => 'required|integer|min:16|max:99',
                'training_goal' => 'required|string|in:muscle_mass,fat_loss,endurance,recovery',
                'dietary_pref' => 'nullable|string',
                'is_vegan' => 'boolean',
                'medical_disclaimer_agreed' => 'required|accepted'
            ]);
            try {
                $dto = new AIStackRequestDto(
                    weightKg: (float)$request->get('weight_kg'),
                    age: (int)$request->get('age'),
                    trainingGoal: $request->get('training_goal'),
                    dietaryPreference: $request->get('dietary_pref', 'standard'),
                    isVegan: (bool)$request->get('is_vegan', false),
                    maxPriceKopecks: (int)$request->get('budget_max', 2000000), // 20k rub default
                    correlationId: $correlationId
                );
                $stack = $this->aiConstructor->constructStack($dto);
                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'stack_result' => [
                        'vertical' => $stack->vertical,
                        'type' => $stack->type,
                        'macro_targets' => $stack->payload['macros'],
                        'supplements' => $stack->payload['items'],
                        'daily_usage' => $stack->payload['usage_guide'],
                        'total_price_rub' => $stack->payload['total_price'] / 100,
                        'suggestions' => $stack->suggestions
                    ]
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('AI stack generation failure', [
                    'cid' => $correlationId,
                    'error' => $e->getMessage()
                ]);
                return response()->json(['error' => 'AI Service Unavailable', 'cid' => $correlationId], 503);
            }
        }
        /**
         * GET /api/v1/sports-nutrition/product/{sku}
         * High density detail view with B2B pricing for authorized partners.
         */
        public function show(string $sku, Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $product = SportsNutritionProduct::where('sku', $sku)
                ->with(['store', 'category'])
                ->firstOrFail();
            $showB2b = $request->user() && $request->user()->can('view_b2b_pricing');
            return response()->json([
                'id' => $product->uuid,
                'name' => $product->name,
                'brand' => $product->brand,
                'price' => $showB2b ? $product->price_b2b : $product->price_b2c,
                'is_b2b' => $showB2b,
                'nutrition_facts' => $product->nutrition_facts,
                'allergens' => $product->allergens,
                'stock' => $product->stock_quantity,
                'servings' => $product->servings_count,
                'is_vegan' => $product->is_vegan,
                'expiry' => $product->expiry_date->toDateString(),
                'tags' => $product->tags,
                'cid' => $correlationId
            ]);
        }
}
