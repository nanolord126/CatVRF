<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Hobby;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HobbyApiController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly AIHobbyConstructor $aiConstructor
        ) {}
        /**
         * GET /api/hobby/catalog
         * List all active DIY materials and tools.
         */
        public function index(Request $request): JsonResponse
        {
            $cid = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                $products = HobbyProduct::where('is_active', true)
                    ->with(['store', 'category'])
                    ->when($request->query('skill'), fn($q) => $q->where('skill_level', $request->query('skill')))
                    ->when($request->query('min_price'), fn($q) => $q->where('price_b2c', '>=', (int)$request->query('min_price')))
                    ->latest()
                    ->paginate(15);
                return response()->json([
                    'success' => true,
                    'data' => $products,
                    'correlation_id' => $cid
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Hobby Catalog Error', ['e' => $e->getMessage(), 'cid' => $cid]);
                return response()->json(['error' => 'Catalog failure'], 500);
            }
        }
        /**
         * POST /api/hobby/match
         * Personalized AI matchmaking for DIY Kits.
         */
        public function matchKits(Request $request): JsonResponse
        {
            $cid = $request->header('X-Correlation-ID', (string) Str::uuid());
            $request->validate([
                'skill_level' => 'required|in:beginner,intermediate,advanced',
                'budget' => 'required|integer|min:100', // 1 ruble min
                'tags' => 'array'
            ]);
            try {
                $dto = HobbyAIRequestDto::fromRequest($request);
                $dto->correlationId = $cid;
                $suggestions = $this->aiConstructor->matchKitsToUser($dto);
                return response()->json([
                    'success' => true,
                    'matched_kits' => $suggestions,
                    'count' => $suggestions->count(),
                    'correlation_id' => $cid
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Hobby AI Match Error', ['e' => $e->getMessage(), 'cid' => $cid]);
                return response()->json(['error' => 'AI logic failure'], 503);
            }
        }
        /**
         * GET /api/hobby/kit/{id}
         * Full kit details including AI prediction feasibility.
         */
        public function showKit(int $id, Request $request): JsonResponse
        {
            $cid = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                $kit = HobbyKit::with(['store', 'tutorials'])->findOrFail($id);
                // AI Prediction surrogate
                $prediction = $this->aiConstructor->predictFeasibility(auth()->id() ?? 0, $id);
                return response()->json([
                    'success' => true,
                    'kit' => $kit,
                    'ai_prediction' => $prediction,
                    'correlation_id' => $cid
                ]);
            } catch (\Throwable $e) {
                return response()->json(['error' => 'Kit not found'], 404);
            }
        }
}
