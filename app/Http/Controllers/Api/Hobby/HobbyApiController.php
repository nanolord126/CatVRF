<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Hobby;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class HobbyApiController extends Controller
{

    public function __construct(
            private readonly AIHobbyConstructor $aiConstructor,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
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
                return $this->response->json([
                    'success' => true,
                    'data' => $products,
                    'correlation_id' => $cid
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Hobby Catalog Error', ['e' => $e->getMessage(), 'cid' => $cid]);
                return $this->response->json(['error' => 'Catalog failure'], 500);
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
                return $this->response->json([
                    'success' => true,
                    'matched_kits' => $suggestions,
                    'count' => $suggestions->count(),
                    'correlation_id' => $cid
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Hobby AI Match Error', ['e' => $e->getMessage(), 'cid' => $cid]);
                return $this->response->json(['error' => 'AI logic failure'], 503);
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
                $prediction = $this->aiConstructor->predictFeasibility($this->guard->id() ?? 0, $id);
                return $this->response->json([
                    'success' => true,
                    'kit' => $kit,
                    'ai_prediction' => $prediction,
                    'correlation_id' => $cid
                ]);
            } catch (\Throwable $e) {
                return $this->response->json(['error' => 'Kit not found'], 404);
            }
        }
}
