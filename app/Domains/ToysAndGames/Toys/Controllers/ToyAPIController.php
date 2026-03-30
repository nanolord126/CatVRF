<?php declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ToyAPIController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly AIToyConstructor $aiConstructor
        ) {}

        /**
         * Search Toys with Age Grouping and Meta filtering.
         * GET /api/v1/toys/search
         */
        public function index(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

            Log::channel('audit')->info('Toy Search Request', [
                'cid' => $correlationId,
                'params' => $request->all()
            ]);

            $query = Toy::with(['store', 'category', 'ageGroup'])
                ->where('is_active', true);

            // Filter by age (months)
            if ($request->has('age_months')) {
                $age = (int) $request->get('age_months');
                $query->whereHas('ageGroup', function ($q) use ($age) {
                    $q->where('min_age_months', '<=', $age)
                      ->where('max_age_months', '>=', $age);
                });
            }

            // Filter by Category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }

            // Filter by Price Range (converted to kopecks)
            if ($request->has('max_price')) {
                $query->where('price_b2c', '<=', (int) $request->get('max_price') * 100);
            }

            // Interest Tags search
            if ($request->has('interests')) {
                $interests = (array) $request->get('interests');
                foreach ($interests as $interest) {
                    $query->whereRaw('LOWER(tags::text) LIKE ?', ['%' . strtolower($interest) . '%']);
                }
            }

            $results = $query->paginate(20);

            return response()->json([
                'cid' => $correlationId,
                'status' => 'success',
                'data' => $results,
                'meta' => [
                    'total' => $results->total(),
                    'version' => '2026.1.0'
                ]
            ])->header('X-Correlation-ID', $correlationId);
        }

        /**
         * AI Consult Assistant for Toy Recommendations.
         * POST /api/v1/toys/ai-consult
         */
        public function aiConsult(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

            $request->validate([
                'age_months' => 'required|integer|min:0',
                'interests' => 'required|array|min:1',
                'budget' => 'required|integer|min:100', // 1 ruble min
                'b2b_mode' => 'boolean'
            ]);

            Log::channel('audit')->info('AI Consultation Started', [
                'cid' => $correlationId,
                'user' => $request->user()?->id ?? 'guest'
            ]);

            $dto = new ToyAIRequestDto(
                userId: $request->user()?->id ?? 0,
                ageMonths: (int) $request->get('age_months'),
                interests: $request->get('interests'),
                budgetLimit: (int) $request->get('budget') * 100, // to kopecks
                educationalOnly: (bool) $request->get('educational_only', false),
                b2bMode: (bool) $request->get('b2b_mode', false)
            );

            $recommendation = $this->aiConstructor->constructRecommendedOffer($dto);

            return response()->json([
                'cid' => $correlationId,
                'status' => 'success',
                'advice' => $recommendation,
            ])->header('X-Correlation-ID', $correlationId);
        }

        /**
         * Toy Detail View with Cross-Sells.
         * GET /api/v1/toys/{uuid}
         */
        public function show(string $uuid): JsonResponse
        {
            $toy = Toy::where('uuid', $uuid)
                ->with(['store', 'category', 'reviews.user'])
                ->firstOrFail();

            return response()->json([
                'status' => 'success',
                'toy' => $toy,
                'average_rating' => $toy->reviews()->avg('rating') ?: 0
            ]);
        }
}
